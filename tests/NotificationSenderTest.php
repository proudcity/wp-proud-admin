<?php

use Brain\Monkey;
use Brain\Monkey\Functions;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Tests for ProudGFSettings::filterNotificationSender() in settings/forms.php.
 *
 * Background (issue #2937): Gravity Forms lets each notification set an
 * arbitrary From address and From name. Two patterns in the wild break email
 * authentication:
 *
 *   1. A From address off our authenticated Mailgun domain -- either a
 *      resident's own address via {Email:6} or a city address such as
 *      townclerk@wendellmass.us. DMARC aligns against the From header domain,
 *      so those fail regardless of which domain we authenticate with.
 *
 *   2. A From name built from resident-name merge tags, producing headers like
 *      "Bruce Ferguson <notify@proudcity.com>". Microsoft weights a personal
 *      display name over an unrelated domain as a phishing signal.
 *
 * The filter runs on gform_notification, which Gravity Forms fires BEFORE
 * merge-tag replacement (common.php:2064, replacement at ~2107). So values
 * here are raw: "{admin_email}", "{Name (First):5.3}". A From name containing
 * a merge tag is therefore resident-derived by definition, which is what makes
 * detection reliable without guessing at what looks like a person's name.
 */
class NotificationSenderTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();

        Functions\when('get_bloginfo')->alias(function (string $show = '') {
            return $show === 'name' ? 'Town of Wendell' : '';
        });
        // Mirrors wp-includes/formatting.php is_email(), NOT filter_var().
        // WordPress is materially laxer -- notably its local-part pattern has
        // no D modifier, so "abc\n" passes. Stubbing with filter_var would hide
        // exactly the header-injection cases these tests need to cover.
        Functions\when('is_email')->alias(function ($email) {
            if (strlen($email) < 6 || substr_count($email, '@') !== 1) {
                return false;
            }
            [$local, $domain] = explode('@', $email, 2);
            if ('' === $local || '' === $domain) {
                return false;
            }
            if (preg_match('/[^a-zA-Z0-9!#$%&\'*+\/=?^_`{|}~\.-]/', $local)) {
                return false;
            }
            if (str_contains($domain, '..') || str_starts_with($domain, '.')
                || str_starts_with($domain, '-') || !str_contains($domain, '.')) {
                return false;
            }
            foreach (explode('.', rtrim($domain, '.')) as $sub) {
                if (!preg_match('/^[a-z0-9-]+$/i', trim($sub, '-'))) {
                    return false;
                }
            }
            return $email;
        });
        Functions\when('apply_filters')->alias(function ($tag, $value) {
            return $value;
        });
    }

    protected function tearDown(): void
    {
        Monkey\tearDown();
        parent::tearDown();
    }

    // -----------------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------------

    private function filter(array $notification): array
    {
        $form = ['id' => 1, 'title' => 'Test form'];
        return ProudGFSettings::filterNotificationSender($notification, $form, []);
    }

    private function notification(array $overrides = []): array
    {
        return array_merge([
            'name'     => 'Admin Notification',
            'to'       => 'clerk@example.gov',
            'from'     => '{admin_email}',
            'fromName' => '',
            'replyTo'  => '',
            'subject'  => 'New submission',
        ], $overrides);
    }

    // -----------------------------------------------------------------------
    // From address: must end up on the authenticated domain
    // -----------------------------------------------------------------------

    public function testAdminEmailMergeTagIsLeftAlone(): void
    {
        $out = $this->filter($this->notification(['from' => '{admin_email}']));

        $this->assertSame('{admin_email}', $out['from']);
        $this->assertSame('', $out['replyTo'], 'Nothing was displaced, so replyTo stays empty.');
    }

    public function testLiteralProudcityAddressIsLeftAlone(): void
    {
        $out = $this->filter($this->notification(['from' => 'notify@proudcity.com']));

        $this->assertSame('notify@proudcity.com', $out['from']);
    }

    public function testProudcitySubdomainAddressIsLeftAlone(): void
    {
        $out = $this->filter($this->notification(['from' => 'alerts@mail.proudcity.com']));

        $this->assertSame('alerts@mail.proudcity.com', $out['from']);
    }

    /** Form 27 on wendellmass.us: From was a city address we cannot sign for. */
    public function testForeignDomainAddressIsReplacedAndMovedToReplyTo(): void
    {
        $out = $this->filter($this->notification(['from' => 'townclerk@wendellmass.us']));

        $this->assertSame('{admin_email}', $out['from']);
        $this->assertSame('townclerk@wendellmass.us', $out['replyTo']);
    }

    /** Form 17 on wendellmass.us: From was the resident's own address. */
    public function testResidentEmailMergeTagIsReplacedAndMovedToReplyTo(): void
    {
        $out = $this->filter($this->notification(['from' => '{Email:6}']));

        $this->assertSame('{admin_email}', $out['from']);
        $this->assertSame('{Email:6}', $out['replyTo']);
    }

    /** Form 16 on wendellmass.us: hyphen instead of underscore, not a real tag. */
    public function testMalformedAdminEmailTagIsReplaced(): void
    {
        $out = $this->filter($this->notification(['from' => '{admin-email}']));

        $this->assertSame('{admin_email}', $out['from']);
        $this->assertSame(
            '',
            $out['replyTo'],
            'A malformed tag is not a usable reply address, so it must not be promoted to replyTo.'
        );
    }

    public function testExistingReplyToIsNeverOverwritten(): void
    {
        $out = $this->filter($this->notification([
            'from'    => 'townclerk@wendellmass.us',
            'replyTo' => 'someone@example.gov',
        ]));

        $this->assertSame('{admin_email}', $out['from']);
        $this->assertSame('someone@example.gov', $out['replyTo']);
    }

    public function testEmptyFromIsNormalisedToAdminEmail(): void
    {
        $out = $this->filter($this->notification(['from' => '']));

        $this->assertSame('{admin_email}', $out['from']);
        $this->assertSame('', $out['replyTo']);
    }

    // -----------------------------------------------------------------------
    // From name: resident-derived names are replaced with the site name
    // -----------------------------------------------------------------------

    public function testResidentNameMergeTagsAreReplacedWithSiteName(): void
    {
        $out = $this->filter($this->notification([
            'fromName' => '{Name (First):5.3} {Name (Last):5.6}',
        ]));

        $this->assertSame('Town of Wendell', $out['fromName']);
    }

    public function testResidentNameMergeTagsWithoutSpaceAreReplaced(): void
    {
        $out = $this->filter($this->notification([
            'fromName' => '{Name (First):5.3}{Name (Last):5.6}',
        ]));

        $this->assertSame('Town of Wendell', $out['fromName']);
    }

    public function testStaticFromNameIsLeftAlone(): void
    {
        $out = $this->filter($this->notification(['fromName' => 'Meeting material form']));

        $this->assertSame(
            'Meeting material form',
            $out['fromName'],
            'An admin typed this deliberately and it is not a personal name.'
        );
    }

    public function testEmptyFromNameIsLeftEmpty(): void
    {
        $out = $this->filter($this->notification(['fromName' => '']));

        $this->assertSame(
            '',
            $out['fromName'],
            'Empty means Gravity Forms supplies its own default; do not start setting one.'
        );
    }

    public function testFromNameFallsBackToFormTitleWhenSiteNameIsBlank(): void
    {
        Functions\when('get_bloginfo')->justReturn('');

        $out = $this->filter($this->notification([
            'fromName' => '{Name (First):5.3}',
        ]));

        $this->assertSame('Test form', $out['fromName']);
    }

    // -----------------------------------------------------------------------
    // Reply-To: prefer the resident's address when we displace their name
    // -----------------------------------------------------------------------

    public function testResidentNameDisplacementDoesNotInventAReplyTo(): void
    {
        $out = $this->filter($this->notification([
            'fromName' => '{Name (First):5.3} {Name (Last):5.6}',
            'from'     => '{admin_email}',
            'replyTo'  => '',
        ]));

        $this->assertSame('Town of Wendell', $out['fromName']);
        $this->assertSame(
            '',
            $out['replyTo'],
            'We have no reliable way to know which field holds the resident email, so leave it.'
        );
    }

    // -----------------------------------------------------------------------
    // Header-injection hardening (security review, #2937)
    // -----------------------------------------------------------------------

    /**
     * WordPress' is_email() accepts a trailing newline in the local part, and
     * From is the one value Gravity Forms does not run through
     * remove_extra_commas(). Such a value must never be treated as sendable.
     *
     */
    #[DataProvider('controlCharacterAddresses')]
    public function testControlCharactersInFromAreNeverTreatedAsAuthenticated(string $from): void
    {
        $out = $this->filter($this->notification(['from' => $from]));

        $this->assertSame('{admin_email}', $out['from']);
        $this->assertSame('', $out['replyTo'], 'Nor may it be promoted into Reply-To.');
    }

    public static function controlCharacterAddresses(): array
    {
        return [
            'trailing LF in local part' => ["abc\n@proudcity.com"],
            'CR in local part'          => ["abc\r@proudcity.com"],
            'NUL in local part'         => ["abc\0@proudcity.com"],
            'LF before domain'          => ["notify\n@proudcity.com"],
        ];
    }

    /**
     * The {Label:ID} shape must not become a way to smuggle CR/LF or quoting
     * characters into a Reply-To header.
     *
     */
    #[DataProvider('unusableMergeTags')]
    public function testHeaderHostileMergeTagsAreNotPromotedToReplyTo(string $from): void
    {
        $out = $this->filter($this->notification(['from' => $from]));

        $this->assertSame('{admin_email}', $out['from']);
        $this->assertSame('', $out['replyTo']);
    }

    public static function unusableMergeTags(): array
    {
        return [
            'CRLF inside the label'   => ["{Foo\r\nBcc x:6}"],
            'quote and angle brackets' => ['{"<>:1}'],
            'NUL inside the label'    => ["{a\0b:1}"],
            'ellipsis is not a field id' => ['{Email:...}'],
            'bare dot is not a field id' => ['{Email:.}'],
            'comma could split the header' => ['{Foo,Bar:6}'],
            'at sign in the label'    => ['{a@b:6}'],
        ];
    }

    /**
     * A trailing newline is stripped before the value is used, so the tag is
     * still usable and what lands in Reply-To carries no control character.
     */
    public function testTrailingWhitespaceIsTrimmedRatherThanRejected(): void
    {
        $out = $this->filter($this->notification(['from' => "{Email:6}\n"]));

        $this->assertSame('{admin_email}', $out['from']);
        $this->assertSame('{Email:6}', $out['replyTo']);
    }

    /** Genuine field merge tags must still survive the tightened pattern. */
    public function testValidFieldMergeTagsAreStillPromoted(): void
    {
        foreach (['{Email:6}', '{Email:5.3}', '{Work Email:12}'] as $tag) {
            $out = $this->filter($this->notification(['from' => $tag]));
            $this->assertSame($tag, $out['replyTo'], "{$tag} should be usable as Reply-To");
        }
    }

    /**
     * Lookalike domains must not pass the allowlist. The leading dot in the
     * suffix check is what makes this work.
     *
     */
    #[DataProvider('lookalikeDomains')]
    public function testLookalikeDomainsAreRejected(string $from): void
    {
        $out = $this->filter($this->notification(['from' => $from]));

        $this->assertSame('{admin_email}', $out['from']);
    }

    public static function lookalikeDomains(): array
    {
        return [
            'prefixed domain'   => ['x@evilproudcity.com'],
            'suffixed domain'   => ['x@proudcity.com.attacker.net'],
            'punycode lookalike' => ['x@xn--proudcity.com'],
            'leading dot'       => ['x@.proudcity.com'],
        ];
    }

    public function testDomainMatchIsCaseInsensitive(): void
    {
        $out = $this->filter($this->notification(['from' => 'notify@PROUDCITY.COM']));

        $this->assertSame('notify@PROUDCITY.COM', $out['from']);
    }

    // -----------------------------------------------------------------------
    // Structural guarantees
    // -----------------------------------------------------------------------

    public function testOtherNotificationKeysAreUntouched(): void
    {
        $in  = $this->notification([
            'from'     => 'townclerk@wendellmass.us',
            'fromName' => '{Name (First):5.3}',
            'bcc'      => 'archive@example.gov',
            'message'  => 'body text',
        ]);
        $out = $this->filter($in);

        $this->assertSame('clerk@example.gov', $out['to']);
        $this->assertSame('New submission', $out['subject']);
        $this->assertSame('archive@example.gov', $out['bcc']);
        $this->assertSame('body text', $out['message']);
    }

    public function testMissingKeysDoNotRaiseNotices(): void
    {
        $out = ProudGFSettings::filterNotificationSender(
            ['to' => 'clerk@example.gov'],
            ['id' => 1, 'title' => 'Test form'],
            []
        );

        $this->assertSame('{admin_email}', $out['from']);
        $this->assertArrayHasKey('replyTo', $out);
    }

    public function testNonArrayNotificationIsReturnedUnchanged(): void
    {
        $out = ProudGFSettings::filterNotificationSender(
            'not an array',
            ['id' => 1, 'title' => 'Test form'],
            []
        );

        $this->assertSame('not an array', $out);
    }
}

<?php

use Brain\Monkey;
use Brain\Monkey\Functions;
use PHPUnit\Framework\TestCase;

/**
 * Tests for ProudMetaBox::save_meta() and ProudTermMetaBox::save_term_meta().
 *
 * From the security review of #2933. The save path had three problems:
 *
 *   1. validate_values() gated on `!empty($screen)` -- an undefined local
 *      variable, not $this->screen -- so the post-type check never ran and any
 *      post type could carry any metabox's meta.
 *   2. No capability check of its own: save_post fires for every post save, so
 *      any user who could save any post could write these fields onto it.
 *   3. No nonce, and save_all() writes raw $_POST values straight to
 *      update_post_meta().
 *
 * That is the input side of the stored-XSS findings in wp-proud-agency:
 * social_*, name_link, phone and friends all arrive through here.
 */
class MetaBoxSaveTest extends TestCase
{
    /** Captures update_post_meta calls: [ key => value ] */
    private array $savedMeta = [];

    /** Captures update_term_meta calls: [ key => value ] */
    private array $savedTermMeta = [];

    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();

        $this->savedMeta     = [];
        $this->savedTermMeta = [];
        $_POST               = [];

        Functions\when('update_post_meta')->alias(function ($id, $key, $value) {
            $this->savedMeta[$key] = $value;
            return true;
        });
        Functions\when('update_term_meta')->alias(function ($id, $key, $value) {
            $this->savedTermMeta[$key] = $value;
            return true;
        });
        Functions\when('get_post_meta')->justReturn([]);
        Functions\when('get_term_meta')->justReturn([]);

        // Permissive by default; individual tests tighten these.
        Functions\when('current_user_can')->justReturn(true);
        Functions\when('wp_verify_nonce')->justReturn(true);
        Functions\when('wp_is_post_revision')->justReturn(false);
        Functions\when('wp_is_post_autosave')->justReturn(false);
        Functions\when('wp_unslash')->returnArg();
        Functions\when('sanitize_text_field')->returnArg();
        Functions\when('wp_nonce_field')->justReturn('');
    }

    protected function tearDown(): void
    {
        $_POST = [];
        Monkey\tearDown();
        parent::tearDown();
    }

    // -----------------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------------

    private function box(string $screen = 'agency'): TestMetaBox
    {
        $box = new TestMetaBox('agency_contact', 'Contact', $screen);
        $box->register_form();
        return $box;
    }

    private function post(string $type = 'agency'): object
    {
        return (object) ['post_type' => $type, 'ID' => 8598];
    }

    /**
     * Populates $_POST the way the metabox form does.
     */
    private function submit(array $fields, string $key = 'agency_contact'): void
    {
        $_POST['form-' . $key] = [1 => $fields];
        $_POST['proud_metabox_nonce_' . $key] = 'valid-nonce';
    }

    // -----------------------------------------------------------------------
    // The happy path must keep working
    // -----------------------------------------------------------------------

    public function test_saves_values_on_a_normal_submission(): void
    {
        $this->submit(['phone' => '(415) 485-3333']);

        $this->box()->save_meta_gate(8598, $this->post(), true);

        $this->assertSame('(415) 485-3333', $this->savedMeta['phone'] ?? null);
    }

    // -----------------------------------------------------------------------
    // 1. Post-type scoping ($screen vs $this->screen)
    // -----------------------------------------------------------------------

    /**
     * The typo: because `$screen` was undefined, this check never fired and an
     * agency metabox would write its meta onto a page, a post, anything.
     */
    public function test_does_not_save_onto_the_wrong_post_type(): void
    {
        $this->submit(['phone' => '(415) 485-3333']);

        $this->box('agency')->save_meta_gate(8598, $this->post("page"), true);

        $this->assertSame([], $this->savedMeta);
    }

    public function test_saves_when_no_screen_is_declared(): void
    {
        $this->submit(['phone' => '(415) 485-3333']);

        $box = new TestMetaBox('agency_contact', 'Contact', null);
        $box->register_form();
        $box->save_meta_gate(8598, $this->post("page"), true);

        $this->assertSame('(415) 485-3333', $this->savedMeta['phone'] ?? null);
    }

    // -----------------------------------------------------------------------
    // 2. Capability
    // -----------------------------------------------------------------------

    public function test_does_not_save_without_edit_post_capability(): void
    {
        Functions\when('current_user_can')->justReturn(false);
        $this->submit(['phone' => '(415) 485-3333']);

        $this->box()->save_meta_gate(8598, $this->post(), true);

        $this->assertSame([], $this->savedMeta);
    }

    /**
     * The capability must be checked against the post being written, not in
     * the abstract.
     */
    public function test_capability_is_checked_against_the_target_post(): void
    {
        $seen = [];
        Functions\when('current_user_can')->alias(function ($cap, $id = null) use (&$seen) {
            $seen[] = [$cap, $id];
            return true;
        });
        $this->submit(['phone' => '(415) 485-3333']);

        $this->box()->save_meta_gate(8598, $this->post(), true);

        $this->assertContains(['edit_post', 8598], $seen);
    }

    // -----------------------------------------------------------------------
    // 3. Nonce
    // -----------------------------------------------------------------------

    public function test_does_not_save_without_a_valid_nonce(): void
    {
        Functions\when('wp_verify_nonce')->justReturn(false);
        $this->submit(['phone' => '(415) 485-3333']);

        $this->box()->save_meta_gate(8598, $this->post(), true);

        $this->assertSame([], $this->savedMeta);
    }

    public function test_does_not_save_when_the_nonce_is_missing(): void
    {
        $_POST['form-agency_contact'] = [1 => ['phone' => '(415) 485-3333']];

        $this->box()->save_meta_gate(8598, $this->post(), true);

        $this->assertSame([], $this->savedMeta);
    }

    // -----------------------------------------------------------------------
    // Autosaves, revisions and non-form saves
    // -----------------------------------------------------------------------

    public function test_does_not_save_on_a_revision(): void
    {
        Functions\when('wp_is_post_revision')->justReturn(8599);
        $this->submit(['phone' => '(415) 485-3333']);

        $this->box()->save_meta_gate(8598, $this->post(), true);

        $this->assertSame([], $this->savedMeta);
    }

    public function test_does_not_save_on_an_autosave(): void
    {
        Functions\when('wp_is_post_autosave')->justReturn(8599);
        $this->submit(['phone' => '(415) 485-3333']);

        $this->box()->save_meta_gate(8598, $this->post(), true);

        $this->assertSame([], $this->savedMeta);
    }

    /**
     * A programmatic save (REST, WP-CLI, wp_update_post) carries none of our
     * fields. It must be a clean no-op, not an error and not a write.
     */
    public function test_programmatic_save_without_our_form_is_a_noop(): void
    {
        $this->box()->save_meta_gate(8598, $this->post(), true);

        $this->assertSame([], $this->savedMeta);
    }

    /**
     * Another metabox's submission must not be picked up by this one.
     */
    public function test_ignores_another_metaboxes_submission(): void
    {
        $this->submit(['phone' => '(415) 485-3333'], 'agency_social');

        $this->box()->save_meta_gate(8598, $this->post(), true);

        $this->assertSame([], $this->savedMeta);
    }

    // -----------------------------------------------------------------------
    // Subclasses that override save_meta()
    // -----------------------------------------------------------------------

    /**
     * Nine subclasses across wp-proud-agency, wp-proud-meeting, wp-proud-location
     * and wp-proud-topic override save_meta() and none call parent::save_meta().
     * Guards living in the base save_meta() were therefore skipped entirely for
     * them, which is why they now live in the final save_meta_gate() wrapper.
     */
    public function test_override_cannot_skip_the_guards(): void
    {
        Functions\when('wp_verify_nonce')->justReturn(false);
        $this->submit(['phone' => '(415) 485-3333']);

        $box = new TestOverridingMetaBox('agency_contact', 'Contact', 'agency');
        $box->register_form();
        $box->save_meta_gate(8598, $this->post(), true);

        $this->assertFalse($box->ran, 'an overriding save_meta() ran despite a bad nonce');
        $this->assertSame([], $this->savedMeta);
    }

    public function test_override_still_runs_on_a_valid_submission(): void
    {
        $this->submit(['phone' => '(415) 485-3333']);

        $box = new TestOverridingMetaBox('agency_contact', 'Contact', 'agency');
        $box->register_form();
        $box->save_meta_gate(8598, $this->post(), true);

        $this->assertTrue($box->ran);
    }

    public function test_gate_is_final(): void
    {
        $method = new ReflectionMethod(ProudMetaBox::class, 'save_meta_gate');

        $this->assertTrue($method->isFinal(), 'save_meta_gate() must not be overridable');
    }

    /**
     * FormHelper lowercases the key when building field names
     * (form-{strtolower(key)}[...]), so a mixed-case metabox key would have
     * silently stopped saving. Latent today -- every key in the tree is already
     * lowercase -- but free to close.
     */
    public function test_mixed_case_key_still_matches_the_form_fields(): void
    {
        $box = new TestMixedCaseMetaBox('Agency_Contact', 'Contact', 'agency');
        $box->register_form();

        $_POST['form-agency_contact'] = [1 => ['phone' => '(415) 485-3333']];
        $_POST[$box->nonce_name()] = 'valid-nonce';

        $box->save_meta_gate(8598, $this->post(), true);

        $this->assertSame('(415) 485-3333', $this->savedMeta['phone'] ?? null);
    }

    // -----------------------------------------------------------------------
    // Term metaboxes
    // -----------------------------------------------------------------------

    public function test_term_meta_saves_on_a_normal_submission(): void
    {
        $this->submit(['phone' => '(415) 485-3333'], 'topic');

        $box = new TestTermMetaBox('topic', 'Topic');
        $box->register_form();
        $box->save_term_meta(42, 'topic');

        $this->assertSame('(415) 485-3333', $this->savedTermMeta['phone'] ?? null);
    }

    public function test_term_meta_requires_capability(): void
    {
        Functions\when('current_user_can')->justReturn(false);
        $this->submit(['phone' => '(415) 485-3333'], 'topic');

        $box = new TestTermMetaBox('topic', 'Topic');
        $box->register_form();
        $box->save_term_meta(42, 'topic');

        $this->assertSame([], $this->savedTermMeta);
    }

    public function test_term_meta_requires_a_valid_nonce(): void
    {
        Functions\when('wp_verify_nonce')->justReturn(false);
        $this->submit(['phone' => '(415) 485-3333'], 'topic');

        $box = new TestTermMetaBox('topic', 'Topic');
        $box->register_form();
        $box->save_term_meta(42, 'topic');

        $this->assertSame([], $this->savedTermMeta);
    }
}

/**
 * Concrete ProudMetaBox with one text field.
 */
class TestMetaBox extends ProudMetaBox
{
    public function set_fields($displaying)
    {
        $this->fields = ['phone' => ['#type' => 'text', '#title' => 'Phone']];
    }

    public function register_form()
    {
        $this->set_fields(false);
        $this->options = ['phone' => ''];
        $this->form = new \Proud\Core\FormHelper('agency_contact', $this->fields, 1, 'form');
    }
}

/**
 * Concrete ProudTermMetaBox with one text field.
 */
class TestTermMetaBox extends ProudTermMetaBox
{
    public function set_fields($displaying)
    {
        $this->fields = ['phone' => ['#type' => 'text', '#title' => 'Phone']];
    }

    public function register_form()
    {
        $this->set_fields(false);
        $this->options = ['phone' => ''];
        $this->form = new \Proud\Core\FormHelper('topic', $this->fields, 1, 'form');
    }
}

/**
 * Mimics the real subclasses: overrides save_meta() without calling parent.
 */
class TestOverridingMetaBox extends TestMetaBox
{
    public bool $ran = false;

    public function save_meta( $post_id, $post, $update )
    {
        $this->ran = true;
    }
}

/**
 * Metabox declared with a mixed-case key.
 */
class TestMixedCaseMetaBox extends ProudMetaBox
{
    public function set_fields($displaying)
    {
        $this->fields = ['phone' => ['#type' => 'text', '#title' => 'Phone']];
    }

    public function register_form()
    {
        $this->set_fields(false);
        $this->options = ['phone' => ''];
        $this->form = new \Proud\Core\FormHelper('Agency_Contact', $this->fields, 1, 'form');
    }
}

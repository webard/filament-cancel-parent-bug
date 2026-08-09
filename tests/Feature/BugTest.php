<?php

namespace Tests\Feature;

use App\Filament\Pages\BugPage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * This test asserts the expected behaviour, so it fails on Filament v4.12.6. The
 * failure is the bug report: after the child modal is closed, no action on the page
 * can be used again.
 */
class BugTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create());
    }

    /**
     * @return array<string>
     */
    protected function mountedActionNames(object $component): array
    {
        return array_column($component->instance()->mountedActions, 'name');
    }

    public function test_other_actions_still_work_after_the_child_modal_is_closed(): void
    {
        $component = Livewire::test(BugPage::class);

        // 1. `openChild` mounts a nested action and halts. Halting deliberately leaves
        // the action mounted, so that its modal stays open for the user to retry.
        $component->call('mountAction', 'openChild');

        $this->assertSame(['openChild', 'child'], $this->mountedActionNames($component));

        // 2. The user closes the child modal. This is the exact call the modal makes on
        // close, with the value that `getParentActionsToCancelOnClose()` resolves to.
        $child = $component->instance()->getMountedAction();
        $component->call('unmountAction', $child->getParentActionsToCancelOnClose());

        // `openChild` is still mounted. It has no schema and no confirmation, so it has
        // no modal of its own: there is now nothing on screen, and no way for the user
        // to reach or dismiss it.
        $this->assertSame(['openChild'], $this->mountedActionNames($component));

        // 3. Every other action on the page is now dead. `resolveActions()` tries to
        // resolve them as children of the stuck action, throws
        // `ActionNotResolvableException`, `cacheMountedActions()` swallows it, and
        // `mountAction()` quietly gives up. Nothing reaches the user or the log.
        $component->call('mountAction', 'other');

        $component->assertNotified('The other action ran.');
    }
}

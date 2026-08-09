<?php

namespace Tests\Feature;

use App\Filament\Pages\BugPage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * On Filament v4.12.6 this fails at the second assertion: closing the child's modal
 * leaves `openChild` mounted with nothing to show, and every action on the page is
 * dead from then on.
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
        //
        // `openChild` has no schema and no confirmation, so it has no modal to stay
        // open in. Nothing about it is on screen, so it must not stay mounted either.
        $child = $component->instance()->getMountedAction();
        $component->call('unmountAction', $child->getParentActionsToCancelOnClose());

        $this->assertSame([], $this->mountedActionNames($component));

        // 3. Every other action on the page still works.
        $component->call('mountAction', 'other')
            ->assertNotified('The other action ran.');
    }

    public function test_an_action_with_a_modal_still_stays_mounted(): void
    {
        // The documented behaviour, which the fix must not change: closing a child's
        // modal returns the user to the parent's modal, rather than losing the flow.
        $component = Livewire::test(BugPage::class)
            ->call('mountAction', 'openChildFromAModal')
            ->call('callMountedAction');

        $this->assertSame(['openChildFromAModal', 'child'], $this->mountedActionNames($component));

        $child = $component->instance()->getMountedAction();
        $component->call('unmountAction', $child->getParentActionsToCancelOnClose());

        $this->assertSame(['openChildFromAModal'], $this->mountedActionNames($component));
    }
}

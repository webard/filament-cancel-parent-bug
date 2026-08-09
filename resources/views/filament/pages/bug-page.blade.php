<x-filament-panels::page>
    <x-filament::section>
        <x-slot name="heading">Steps</x-slot>

        <ol>
            <li>Click <strong>1. Open the child modal</strong>. The child modal opens.</li>
            <li>Close that modal with the X, with Escape, or by clicking away.</li>
            <li>Click <strong>2. Any other action</strong>.</li>
        </ol>

        <p>
            <strong>Expected:</strong> the second action runs and a notification appears.
        </p>

        <p>
            <strong>Actual:</strong> nothing happens. No modal, no notification, no error in the
            console or the log. Every action on the page stays dead until you reload it.
        </p>
    </x-filament::section>

    <x-filament::section>
        <x-slot name="heading">Why</x-slot>

        <p>
            The first action has no modal of its own, so once the child modal is closed the
            action is still mounted with nothing to render. From then on
            <code>resolveActions()</code> tries to resolve every newly mounted action as a
            child of it, throws <code>ActionNotResolvableException</code>,
            <code>cacheMountedActions()</code> swallows it, and <code>mountAction()</code>
            quietly gives up.
        </p>

        <p>
            To watch the stack get stuck, run this in the browser console after each step.
            It reads the live component state, which the page body does not show, because
            mounting an action only re-renders the modal partial:
        </p>

        <pre><code>Livewire.all().find(c =&gt; c.name.endsWith('bug-page')).$wire.mountedActions</code></pre>
    </x-filament::section>
</x-filament-panels::page>

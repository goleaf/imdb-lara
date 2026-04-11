<x-ui.input.options.button
    x-data="inputCopyAction()"
    x-on:click="doCopy()"
    x-bind:data-slot-copied="copied"
    x-bind:aria-label="copied ? 'Copied!' : 'Copy to clipboard'"
    x-bind:title="copied ? 'Copied!' : 'Copy to clipboard'"
>     
    <x-ui.icon 
        name="clipboard-document-check" 
        class="hidden [[data-slot-copied]>&]:inline-flex" 
        aria-hidden="true"
    />
    <x-ui.icon 
        name="clipboard-document" 
        class="inline-flex [[data-slot-copied]>&]:hidden" 
        aria-hidden="true"
    />
</x-ui.input.options.button>

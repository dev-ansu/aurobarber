@if(config('services.turnstile.site_key'))
    <div class="my-4 flex flex-col items-center justify-center" wire:ignore>
        <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
        <div class="cf-turnstile"
             data-sitekey="{{ config('services.turnstile.site_key') }}"
             data-action="login"
             data-theme="dark"
             data-callback="onFilamentTurnstileSuccess"
             data-expired-callback="onFilamentTurnstileExpired"></div>
        <script>
            function onFilamentTurnstileSuccess(token) {
                const el = document.querySelector('[wire\\:id]');
                if (el && window.Livewire) {
                    const component = Livewire.find(el.getAttribute('wire:id'));
                    if (component) {
                        component.set('turnstileToken', token);
                    }
                }
            }
            function onFilamentTurnstileExpired() {
                const el = document.querySelector('[wire\\:id]');
                if (el && window.Livewire) {
                    const component = Livewire.find(el.getAttribute('wire:id'));
                    if (component) {
                        component.set('turnstileToken', null);
                    }
                }
            }
        </script>
    </div>
@endif

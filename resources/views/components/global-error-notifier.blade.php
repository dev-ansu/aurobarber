<div
    id="aurobarber-global-toast-container"
    style="position: fixed; top: 1.25rem; right: 1.25rem; z-index: 99999; display: flex; flex-direction: column; gap: 0.75rem; max-width: 26rem; width: calc(100vw - 2.5rem); pointer-events: none;"
></div>

<script>
    (function () {
        if (window.__aurobarberErrorInterceptorInstalled) return;
        window.__aurobarberErrorInterceptorInstalled = true;

        window.showAurobarberToast = function (title, message, type = 'danger') {
            try {
                if (typeof window.FilamentNotification === 'function') {
                    const notif = new window.FilamentNotification()
                        .title(title)
                        .body(message);
                    if (type === 'warning') notif.warning();
                    else if (type === 'success') notif.success();
                    else notif.danger();
                    notif.send();
                    return;
                }
            } catch (e) {
                // Fallback para o toast nativo abaixo
            }

            const container = document.getElementById('aurobarber-global-toast-container');
            if (!container) return;

            const toast = document.createElement('div');
            const isWarning = type === 'warning';
            const borderColor = isWarning ? 'rgba(245, 158, 11, 0.45)' : 'rgba(239, 68, 68, 0.45)';
            const accentColor = isWarning ? '#f59e0b' : '#ef4444';
            const iconText = isWarning ? '⚠️' : '🚨';

            toast.style.cssText = [
                'pointer-events: auto',
                'display: flex',
                'align-items: flex-start',
                'gap: 0.75rem',
                'padding: 0.9rem 1rem',
                'border-radius: 1rem',
                'background: rgba(15, 23, 42, 0.96)',
                'color: #f8fafc',
                'border: 1px solid ' + borderColor,
                'box-shadow: 0 20px 25px -5px rgba(0,0,0,0.45), 0 8px 10px -6px rgba(0,0,0,0.35)',
                'backdrop-filter: blur(12px)',
                'font-family: inherit',
                'transition: all 0.25s ease',
                'transform: translateY(-8px)',
                'opacity: 0'
            ].join(';');

            toast.innerHTML = `
                <div style="font-size: 1.15rem; line-height: 1; margin-top: 0.1rem;">${iconText}</div>
                <div style="flex: 1; min-width: 0;">
                    <div style="font-size: 0.82rem; font-weight: 700; color: #ffffff; margin-bottom: 0.2rem;"></div>
                    <div style="font-size: 0.75rem; line-height: 1.4; color: #cbd5e1;"></div>
                </div>
                <button type="button" style="background: transparent; border: 0; color: #94a3b8; cursor: pointer; font-size: 0.95rem; line-height: 1; padding: 0.15rem;">✕</button>
            `;

            const titleEl = toast.querySelectorAll('div')[2];
            const msgEl = toast.querySelectorAll('div')[3];
            const closeBtn = toast.querySelector('button');

            if (titleEl) titleEl.textContent = title || 'Atenção';
            if (msgEl) msgEl.textContent = message || 'Não foi possível concluir a operação.';

            const removeToast = () => {
                toast.style.opacity = '0';
                toast.style.transform = 'translateY(-8px)';
                setTimeout(() => toast.remove(), 250);
            };

            if (closeBtn) closeBtn.addEventListener('click', removeToast);
            container.appendChild(toast);

            requestAnimationFrame(() => {
                toast.style.opacity = '1';
                toast.style.transform = 'translateY(0)';
            });

            setTimeout(removeToast, 6500);
        };

        document.addEventListener('livewire:init', () => {
            if (!window.Livewire || typeof window.Livewire.hook !== 'function') return;

            window.Livewire.hook('request', ({ fail }) => {
                fail(({ status, content, preventDefault }) => {
                    preventDefault();

                    let title = 'Não foi possível concluir a ação';
                    let message = 'Ocorreu uma falha ao processar sua solicitação. Verifique os dados e tente novamente.';
                    let type = 'danger';

                    if (status === 419) {
                        title = 'Sessão Expirada';
                        message = 'Sua sessão ficou inativa por algum tempo. Recarregando a página automaticamente...';
                        type = 'warning';
                        window.showAurobarberToast(title, message, type);
                        setTimeout(() => window.location.reload(), 1800);
                        return;
                    }

                    if (status === 413) {
                        title = 'Arquivo ou envio muito grande';
                        message = 'O tamanho do arquivo enviado excedeu o limite permitido pela requisição.';
                    } else if (status === 403) {
                        title = 'Acesso Restrito';
                        message = 'Você não possui permissão para realizar esta ação.';
                        type = 'warning';
                    } else if (status === 404) {
                        title = 'Registro não encontrado';
                        message = 'O item solicitado não foi localizado ou já foi removido.';
                        type = 'warning';
                    }

                    if (content) {
                        try {
                            const parsed = JSON.parse(content);
                            if (parsed && parsed.title) {
                                title = parsed.title;
                            }
                            if (parsed && parsed.message) {
                                const rawMsg = String(parsed.message);
                                if (rawMsg.includes('PayloadTooLargeException') || rawMsg.includes('payload is too large')) {
                                    title = 'Arquivo ou envio muito grande';
                                    message = 'O volume de dados enviados excedeu o limite por pacote. Tente novamente.';
                                } else if (!rawMsg.includes('SQLSTATE') && !rawMsg.includes('vendor/')) {
                                    message = rawMsg;
                                }
                            }
                        } catch (err) {
                            // Resposta HTML — mantém mensagem amigável
                        }
                    }

                    window.showAurobarberToast(title, message, type);
                });
            });
        });
    })();
</script>

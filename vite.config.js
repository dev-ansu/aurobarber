import os from 'os';
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import tailwindcss from '@tailwindcss/vite';

function getLocalNetworkIp() {
    const nets = os.networkInterfaces();
    for (const name of Object.keys(nets)) {
        for (const net of nets[name] || []) {
            if (
                net.family === 'IPv4' &&
                !net.internal &&
                (net.address.startsWith('192.168.') ||
                    net.address.startsWith('10.') ||
                    /^172\.(1[6-9]|2\d|3[0-1])\./.test(net.address))
            ) {
                return net.address;
            }
        }
    }
    return 'localhost';
}

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js', 'resources/css/filament/admin/theme.css'],
            refresh: true,
            fonts: [
                bunny('Instrument Sans', {
                    weights: [400, 500, 600],
                }),
            ],
        }),
        tailwindcss(),
    ],
    server: {
        host: '0.0.0.0',
        cors: true,
        hmr: {
            host: getLocalNetworkIp(),
        },
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});

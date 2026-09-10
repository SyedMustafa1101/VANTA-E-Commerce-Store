import { createServer } from 'node:http';
import { readFile } from 'node:fs/promises';
import { extname, join, normalize } from 'node:path';

const root = process.cwd();
const port = 4173;

const icons = {
    'arrow-up-right': '<path d="M7 17 17 7M8 7h9v9"/>',
    'arrow-right': '<path d="M5 12h14M13 6l6 6-6 6"/>',
    bag: '<path d="M6 8h12l1 12H5L6 8Z"/><path d="M9 9V6a3 3 0 0 1 6 0v3"/>',
    heart: '<path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.7l-1.1-1.1a5.5 5.5 0 0 0-7.8 7.8l1.1 1.1L12 21l7.7-7.5 1.1-1.1a5.5 5.5 0 0 0 0-7.8Z"/>',
    plus: '<path d="M12 5v14M5 12h14"/>',
    minus: '<path d="M5 12h14"/>',
};

function renderPhp(source, page) {
    const isSystem = page === 'design-system.php';
    const title = isSystem ? 'VANTA — Design System' : 'VANTA — Foundation';
    const bodyClass = isSystem ? 'system-page' : 'foundation-page';
    const currentPage = isSystem ? 'design-system' : 'home';
    const headerTheme = isSystem ? 'solid' : 'overlay';

    return source
        .replace(/<\?php foreach \(str_split\('VANTA'\) as \$letter\): \?>[\s\S]*?<\?php endforeach; \?>/g, '<span>V</span><span>A</span><span>N</span><span>T</span><span>A</span>')
        .replace(/<\?= e\(asset\('([^']+)'\)\) \?>/g, '/assets/$1')
        .replace(/<\?= e\(url\('([^']*)'\)\) \?>/g, '/$1')
        .replace(/<\?= icon\('([^']+)',\s*'([^']*)'\) \?>/g, (_, name, className) => `<svg class="${className}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="square" stroke-linejoin="miter" aria-hidden="true">${icons[name] || ''}</svg>`)
        .replace(/<\?= icon\('([^']+)'\) \?>/g, (_, name) => `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true">${icons[name] || ''}</svg>`)
        .replace(/<\?= e\(\$pageTitle\) \?>/g, title)
        .replace(/<\?= e\(\$pageDescription\) \?>/g, 'Premium contemporary streetwear built for after dark.')
        .replace(/<\?= e\(\$bodyClass\) \?>/g, bodyClass)
        .replace(/<\?= e\(\$currentPage\) \?>/g, currentPage)
        .replace(/<\?= e\(\$headerTheme\) \?>/g, headerTheme)
        .replace(/<\?= e\(config\('name', 'VANTA'\)\) \?>/g, 'VANTA')
        .replace(/<\?= e\(date\('Y'\)\) \?>/g, '2026')
        .replace(/<\?=[\s\S]*?\?>/g, '')
        .replace(/<\?php[\s\S]*?\?>/g, '');
}

async function renderPage(page) {
    const [header, content, footer] = await Promise.all([
        readFile(join(root, 'includes', 'header.php'), 'utf8'),
        readFile(join(root, page), 'utf8'),
        readFile(join(root, 'includes', 'footer.php'), 'utf8'),
    ]);
    const main = content.match(/<main[\s\S]*?<\/main>/)?.[0] || '';
    return renderPhp(`${header}${main}${footer}`, page);
}

const mime = {
    '.css': 'text/css; charset=utf-8',
    '.js': 'text/javascript; charset=utf-8',
    '.jpg': 'image/jpeg',
    '.jpeg': 'image/jpeg',
    '.png': 'image/png',
    '.svg': 'image/svg+xml',
};

createServer(async (request, response) => {
    try {
        const url = new URL(request.url || '/', `http://${request.headers.host}`);
        const page = url.pathname === '/design-system.php' ? 'design-system.php' : 'index.php';

        if (url.pathname === '/' || url.pathname === '/index.php' || url.pathname === '/design-system.php') {
            const html = await renderPage(page);
            response.writeHead(200, { 'Content-Type': 'text/html; charset=utf-8' });
            response.end(html);
            return;
        }

        if (url.pathname.startsWith('/assets/')) {
            const requested = normalize(url.pathname.replace(/^\/+/, ''));
            if (!requested.startsWith(`assets${process.platform === 'win32' ? '\\' : '/'}`)) throw new Error('Invalid asset path');
            const file = await readFile(join(root, requested));
            response.writeHead(200, { 'Content-Type': mime[extname(requested)] || 'application/octet-stream' });
            response.end(file);
            return;
        }

        response.writeHead(404, { 'Content-Type': 'text/plain; charset=utf-8' });
        response.end('Not found');
    } catch (error) {
        response.writeHead(500, { 'Content-Type': 'text/plain; charset=utf-8' });
        response.end(error instanceof Error ? error.message : 'Preview error');
    }
}).listen(port, '127.0.0.1', () => {
    console.log(`VANTA preview ready at http://127.0.0.1:${port}`);
});


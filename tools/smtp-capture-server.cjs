'use strict';

const net = require('node:net');

const host = '127.0.0.1';
const port = 1025;
let captured = '';

const server = net.createServer((socket) => {
    let buffer = '';
    let receivingData = false;
    socket.setEncoding('utf8');
    socket.write('220 localhost VANTA SMTP capture\r\n');

    socket.on('data', (chunk) => {
        buffer += chunk;
        while (buffer.includes('\r\n')) {
            const boundary = buffer.indexOf('\r\n');
            const line = buffer.slice(0, boundary);
            buffer = buffer.slice(boundary + 2);

            if (receivingData) {
                if (line === '.') {
                    receivingData = false;
                    socket.write('250 2.0.0 captured\r\n');
                } else {
                    captured += line + '\r\n';
                }
                continue;
            }

            const command = line.toUpperCase();
            if (command.startsWith('EHLO') || command.startsWith('HELO')) {
                socket.write('250-localhost\r\n250-8BITMIME\r\n250 SMTPUTF8\r\n');
            } else if (command.startsWith('MAIL FROM:') || command.startsWith('RCPT TO:')) {
                socket.write('250 2.1.0 accepted\r\n');
            } else if (command === 'DATA') {
                receivingData = true;
                socket.write('354 End data with <CR><LF>.<CR><LF>\r\n');
            } else if (command === 'QUIT') {
                socket.write('221 2.0.0 closing\r\n');
                socket.end();
                // PHPMailer may RFC 2047-encode the UTF-8 subject, so verify the
                // header exists here; the PHP integration suite verifies its value.
                const hasSubject = /^Subject:/mi.test(captured);
                const hasHtml = captured.includes('Content-Type: text/html');
                const hasText = captured.includes('Content-Type: text/plain');
                if (!hasSubject || !hasHtml || !hasText) {
                    console.error('SMTP_CAPTURE_INVALID');
                    process.exitCode = 1;
                } else {
                    console.log('SMTP_CAPTURE_OK multipart=' + captured.length);
                }
                server.close();
            } else {
                socket.write('250 2.0.0 accepted\r\n');
            }
        }
    });
});

server.listen(port, host, () => {
    console.log('SMTP_CAPTURE_READY ' + host + ':' + port);
});

server.on('error', (error) => {
    console.error(error.message);
    process.exitCode = 1;
});

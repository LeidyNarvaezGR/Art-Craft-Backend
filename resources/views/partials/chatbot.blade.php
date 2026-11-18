
<meta name="csrf-token" content="{{ csrf_token() }}">

<div id="artcraft-chat">
    <button id="chat-toggle" type="button">
        💬
    </button>

    <div id="chat-window" style="display:none;">

        <div class="chat-header">
            <div>
                <strong>Art Craft</strong>
                <small>Asistente virtual de compras</small>
            </div>

            <button id="chat-close" type="button">✕</button>
        </div>

        <div id="chat-messages">
            <div class="bot-message">
                ¡Hola! 👋 Bienvenido a Art Craft.
                Soy tu asistente virtual.
                ¿Qué producto artesanal estás buscando?
            </div>
        </div>

        <form id="chat-form">
            <input
                type="text"
                id="chat-input"
                placeholder="Escribe tu pregunta..."
                maxlength="1000"
                required
            >

            <button type="submit">Enviar</button>
        </form>

    </div>
</div>

<style>
#artcraft-chat {
    position: fixed;
    bottom: 25px;
    right: 25px;
    z-index: 9999;
    font-family: Arial, sans-serif;
}

#chat-toggle {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    border: none;
    background: #895b3c;
    color: white;
    font-size: 26px;
    cursor: pointer;
}

#chat-window {
    width: 340px;
    height: 450px;
    background: #fffaf4;
    border-radius: 15px;
    box-shadow: 0 5px 25px #0003;
    overflow: hidden;
    margin-bottom: 12px;
}

.chat-header {
    background: #895b3c;
    color: white;
    padding: 16px;
    display: flex;
    justify-content: space-between;
}

.chat-header small {
    display: block;
    margin-top: 5px;
}

#chat-close {
    background: transparent;
    color: white;
    border: none;
    cursor: pointer;
    font-size: 18px;
}

#chat-messages {
    height: 320px;
    padding: 12px;
    overflow-y: auto;
}

.bot-message, .user-message {
    padding: 10px;
    border-radius: 12px;
    margin-bottom: 10px;
    max-width: 85%;
    white-space: pre-wrap;
    overflow-wrap: anywhere;
}

.bot-message {
    background: #eee2d4;
    color: #392b21;
}

.user-message {
    background: #895b3c;
    color: white;
    margin-left: auto;
}

#chat-form {
    display: flex;
    padding: 10px;
    gap: 5px;
}

#chat-input {
    width: 70%;
    min-width: 0;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 8px;
}

#chat-form button {
    background: #895b3c;
    color: white;
    border: none;
    border-radius: 8px;
    padding: 10px;
    cursor: pointer;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', () => {

    const toggle = document.getElementById('chat-toggle');
    const windowChat = document.getElementById('chat-window');
    const close = document.getElementById('chat-close');
    const form = document.getElementById('chat-form');
    const input = document.getElementById('chat-input');
    const messages = document.getElementById('chat-messages');

    let historial = [];

    toggle.addEventListener('click', () => {
        windowChat.style.display =
            windowChat.style.display === 'none' ? 'block' : 'none';
    });

    close.addEventListener('click', () => {
        windowChat.style.display = 'none';
    });

    function agregarMensaje(texto, clase) {
        const div = document.createElement('div');
        div.className = clase;
        div.textContent = texto;
        messages.appendChild(div);
        messages.scrollTop = messages.scrollHeight;
        return div;
    }

    form.addEventListener('submit', async (e) => {
        e.preventDefault();

        const mensaje = input.value.trim();

        if (!mensaje) return;

        agregarMensaje(mensaje, 'user-message');
        input.value = '';

        const cargando = agregarMensaje(
            'Art Craft está escribiendo...',
            'bot-message'
        );

        try {
            const response = await fetch(
                "{{ route('chatbot.mensaje') }}",
                {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN':
                            document.querySelector(
                                'meta[name="csrf-token"]'
                            ).content
                    },
                    body: JSON.stringify({
                        mensaje: mensaje,
                        historial: historial
                    })
                }
            );

            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.error || 'Error de conexión');
            }

            cargando.textContent = data.respuesta;

            historial.push(
                { role: 'user', text: mensaje },
                { role: 'model', text: data.respuesta }
            );

            historial = historial.slice(-10);

        } catch (error) {
            cargando.textContent =
                error.message || 'No pudimos responder. Intenta nuevamente.';
        }
    });
});
</script>

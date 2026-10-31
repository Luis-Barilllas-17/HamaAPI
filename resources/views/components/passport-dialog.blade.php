<dialog id="passport-dialog" class="passport-dialog" aria-labelledby="passport-dialog-title">
    <form id="passport-form">
        <span class="eyebrow">ACCESO LOCAL</span>
        <h2 id="passport-dialog-title">Conectar con ProyectoAPI</h2>
        <p>Pega un Bearer token generado desde Configuración o Postman. Se conservará únicamente durante esta sesión del navegador.</p>
        <label for="passport-token">Token Bearer</label>
        <textarea id="passport-token" rows="4" placeholder="eyJ0eXAiOiJKV1QiLCJhbGci..."></textarea>
        <div class="passport-dialog-actions">
            <button id="clear-passport-token" class="cancel-button" type="button">Borrar token</button>
            <button class="cancel-button" type="button" onclick="this.closest('dialog').close()">Cancelar</button>
            <button class="primary-button" type="submit">Guardar acceso</button>
        </div>
    </form>
</dialog>

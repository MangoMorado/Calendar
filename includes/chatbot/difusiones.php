<div class="tab-pane fade" id="tab-difusiones" role="tabpanel">
    <div class="form-section">
        <h2><i class="bi bi-megaphone"></i> Difusiones</h2>
        <div class="alert alert-info">
            <i class="bi bi-info-circle"></i>
            <strong>Nota:</strong> Los contactos seleccionados en la pestaña "Contactos" recibirán esta difusión.
        </div>
        <form id="formDifusion" enctype="multipart/form-data" autocomplete="off">
            <div class="mb-3">
                <label for="mensajeDifusion" class="form-label">Mensaje de difusión</label>
                <textarea id="mensajeDifusion" name="mensaje" class="form-control" rows="3" placeholder="Escribe tu mensaje aquí..."></textarea>
            </div>
            <div class="mb-3">
                <label for="imagenDifusion" class="form-label">Imagen (opcional)</label>
                <input type="file" id="imagenDifusion" name="imagen" class="form-control" accept="image/*">
            </div>
            <button type="submit" class="btn btn-success">
                <i class="bi bi-send"></i> Enviar difusión
            </button>
        </form>
    </div>
</div> 
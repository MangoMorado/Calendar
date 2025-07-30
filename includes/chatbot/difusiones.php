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
                <label for="imagenDifusion" class="form-label">Adjunto</label>
                <input type="file" id="imagenDifusion" name="imagen" class="form-control" accept="image/*,video/*,audio/*,.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.rtf,.csv,.json,.xml,.html,.css,.js,.zip,.rar,.7z,.tar,.gz">
                <small class="form-text text-muted">Formatos soportados: Imágenes (JPG, PNG, GIF, WebP, BMP, SVG, ICO, TIFF), Videos (MP4, AVI, MOV, WMV, FLV, WebM, MKV, 3GP, M4V), Audio (MP3, WAV, OGG, AAC, WMA, FLAC, M4A), Documentos (PDF, DOC, DOCX, XLS, XLSX, PPT, PPTX, TXT, RTF, CSV, JSON, XML, HTML, CSS, JS), Comprimidos (ZIP, RAR, 7Z, TAR, GZ). Tamaño máximo: 5MB</small>
            </div>
            <button type="submit" class="btn btn-success">
                <i class="bi bi-send"></i> Enviar difusión
            </button>
        </form>
    </div>
</div> 
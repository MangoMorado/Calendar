import 'quill/dist/quill.snow.css';

import Quill from 'quill';
import { useEffect, useRef } from 'react';

const toolbarOptions = [
    [{ header: [1, 2, 3, false] }],
    [{ font: [] }],
    [{ align: [] }],
    ['bold', 'italic', 'underline', 'strike'],
    [{ list: 'ordered' }, { list: 'bullet' }],
    ['link'],
    ['clean'],
];

/**
 * Formatos permitidos (whitelist). Se excluye "direction" para forzar
 * siempre LTR y evitar texto en espejo / RTL. Ver Quill Config y Formats.
 */
const ALLOWED_FORMATS = [
    'background',
    'bold',
    'color',
    'font',
    'italic',
    'link',
    'size',
    'strike',
    'underline',
    'blockquote',
    'code-block',
    'header',
    'indent',
    'list',
    'align',
] as const;

const EDITOR_CLASS =
    'rounded-md border border-input bg-background [&_.ql-toolbar]:rounded-t-md [&_.ql-container]:rounded-b-md [&_.ql-editor]:min-h-[200px] [&_.ql-editor]:text-left [&_.ql-editor]:[direction:ltr]';

type NoteEditorProps = {
    value: string;
    onChange: (value: string) => void;
    placeholder?: string;
    className?: string;
};

export function NoteEditor({
    value,
    onChange,
    placeholder,
    className,
}: NoteEditorProps) {
    const containerRef = useRef<HTMLDivElement>(null);
    const quillRef = useRef<InstanceType<typeof Quill> | null>(null);
    const prevValueRef = useRef(value);
    const lastEmittedRef = useRef<string | null>(null);
    const onChangeRef = useRef(onChange);

    useEffect(() => {
        onChangeRef.current = onChange;
    }, [onChange]);

    useEffect(() => {
        const wrapper = containerRef.current;
        if (!wrapper) return;

        // Usar un contenedor DOM nuevo evita toolbars duplicados con React Strict Mode.
        const container = document.createElement('div');
        container.className = 'quill-wrapper';
        wrapper.appendChild(container);

        const quill = new Quill(container, {
            theme: 'snow',
            placeholder: placeholder ?? '',
            modules: { toolbar: toolbarOptions },
            formats: [...ALLOWED_FORMATS],
        });
        quillRef.current = quill;

        quill.root.setAttribute('dir', 'ltr');
        const editorEl = quill.root.querySelector<HTMLElement>('.ql-editor');
        if (editorEl) {
            editorEl.setAttribute('dir', 'ltr');
            editorEl.style.direction = 'ltr';
            editorEl.style.unicodeBidi = 'isolate';
        }

        const delta = quill.clipboard.convert({ html: value || '' });
        quill.setContents(delta, 'silent');
        prevValueRef.current = value;

        const handler = () => {
            const html = quill.getSemanticHTML();
            lastEmittedRef.current = html;
            onChangeRef.current(html);
        };
        quill.on('text-change', handler);

        return () => {
            quill.off('text-change', handler);
            quillRef.current = null;
            if (container.parentNode) {
                container.parentNode.removeChild(container);
            }
        };
        // eslint-disable-next-line react-hooks/exhaustive-deps -- value solo para contenido inicial; actualizaciones en el efecto [value]
    }, [placeholder]);

    useEffect(() => {
        const quill = quillRef.current;
        if (!quill) return;
        if (prevValueRef.current === value) return;
        if (value === lastEmittedRef.current) return;

        prevValueRef.current = value;
        lastEmittedRef.current = null;
        const delta = quill.clipboard.convert({ html: value || '' });
        quill.setContents(delta, 'silent');
    }, [value]);

    return (
        <div
            ref={containerRef}
            className={`${EDITOR_CLASS} ${className ?? ''}`}
            dir="ltr"
        />
    );
}

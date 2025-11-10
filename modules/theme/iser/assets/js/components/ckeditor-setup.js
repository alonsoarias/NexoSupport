/**
 * Configuración de CKEditor para el tema ISER
 * @version 1.0.0
 */

class ISERCKEditor {
    constructor() {
        this.editors = new Map();
    }

    /**
     * Inicializar CKEditor en textareas con clase .ckeditor
     */
    static init(selector = '.ckeditor') {
        const textareas = document.querySelectorAll(selector);
        const instance = new ISERCKEditor();

        textareas.forEach(textarea => {
            instance.createEditor(textarea);
        });

        return instance;
    }

    /**
     * Crear editor en un textarea
     */
    async createEditor(textarea) {
        if (typeof ClassicEditor === 'undefined') {
            console.warn('CKEditor no está cargado');
            return null;
        }

        try {
            const editor = await ClassicEditor.create(textarea, this.getConfig());

            // Guardar referencia
            this.editors.set(textarea.id || textarea.name, editor);
            textarea.editor = editor;

            // Sincronizar con el textarea
            editor.model.document.on('change:data', () => {
                textarea.value = editor.getData();
            });

            console.log('Editor CKEditor creado:', editor);
            return editor;
        } catch (error) {
            console.error('Error creando CKEditor:', error);
            return null;
        }
    }

    /**
     * Obtener configuración del editor
     */
    getConfig() {
        return {
            language: 'es',
            toolbar: {
                items: [
                    'heading', '|',
                    'bold', 'italic', 'underline', 'strikethrough', '|',
                    'fontColor', 'fontBackgroundColor', '|',
                    'link', 'blockQuote', 'codeBlock', '|',
                    'bulletedList', 'numberedList', 'todoList', '|',
                    'alignment', '|',
                    'insertTable', 'imageInsert', 'mediaEmbed', '|',
                    'undo', 'redo', '|',
                    'sourceEditing'
                ],
                shouldNotGroupWhenFull: true
            },
            heading: {
                options: [
                    { model: 'paragraph', title: 'Párrafo', class: 'ck-heading_paragraph' },
                    { model: 'heading1', view: 'h1', title: 'Título 1', class: 'ck-heading_heading1' },
                    { model: 'heading2', view: 'h2', title: 'Título 2', class: 'ck-heading_heading2' },
                    { model: 'heading3', view: 'h3', title: 'Título 3', class: 'ck-heading_heading3' },
                    { model: 'heading4', view: 'h4', title: 'Título 4', class: 'ck-heading_heading4' }
                ]
            },
            link: {
                addTargetToExternalLinks: true,
                defaultProtocol: 'https://',
                decorators: {
                    openInNewTab: {
                        mode: 'manual',
                        label: 'Abrir en nueva pestaña',
                        attributes: {
                            target: '_blank',
                            rel: 'noopener noreferrer'
                        }
                    }
                }
            },
            image: {
                toolbar: [
                    'imageTextAlternative', '|',
                    'imageStyle:alignLeft',
                    'imageStyle:alignCenter',
                    'imageStyle:alignRight', '|',
                    'linkImage'
                ],
                styles: [
                    'alignLeft',
                    'alignCenter',
                    'alignRight'
                ]
            },
            table: {
                contentToolbar: [
                    'tableColumn', 'tableRow', 'mergeTableCells',
                    'tableProperties', 'tableCellProperties'
                ],
                tableProperties: {
                    borderColors: [
                        { color: 'hsl(0, 0%, 0%)', label: 'Negro' },
                        { color: 'hsl(0, 0%, 30%)', label: 'Gris oscuro' },
                        { color: 'hsl(0, 0%, 60%)', label: 'Gris' },
                        { color: 'hsl(0, 0%, 90%)', label: 'Gris claro' },
                        { color: 'hsl(0, 0%, 100%)', label: 'Blanco' }
                    ],
                    backgroundColors: [
                        { color: 'hsl(0, 75%, 60%)', label: 'Rojo' },
                        { color: 'hsl(30, 75%, 60%)', label: 'Naranja' },
                        { color: 'hsl(60, 75%, 60%)', label: 'Amarillo' },
                        { color: 'hsl(90, 75%, 60%)', label: 'Verde claro' },
                        { color: 'hsl(120, 75%, 60%)', label: 'Verde' },
                        { color: 'hsl(150, 75%, 60%)', label: 'Turquesa' },
                        { color: 'hsl(180, 75%, 60%)', label: 'Cyan' },
                        { color: 'hsl(210, 75%, 60%)', label: 'Azul claro' },
                        { color: 'hsl(240, 75%, 60%)', label: 'Azul' }
                    ]
                },
                tableCellProperties: {
                    borderColors: [
                        { color: 'hsl(0, 0%, 0%)', label: 'Negro' },
                        { color: 'hsl(0, 0%, 30%)', label: 'Gris oscuro' },
                        { color: 'hsl(0, 0%, 60%)', label: 'Gris' },
                        { color: 'hsl(0, 0%, 90%)', label: 'Gris claro' },
                        { color: 'hsl(0, 0%, 100%)', label: 'Blanco' }
                    ],
                    backgroundColors: [
                        { color: 'hsl(0, 75%, 60%)', label: 'Rojo' },
                        { color: 'hsl(30, 75%, 60%)', label: 'Naranja' },
                        { color: 'hsl(60, 75%, 60%)', label: 'Amarillo' },
                        { color: 'hsl(90, 75%, 60%)', label: 'Verde claro' },
                        { color: 'hsl(120, 75%, 60%)', label: 'Verde' }
                    ]
                }
            },
            licenseKey: '', // GPL license
            placeholder: 'Escribe tu contenido aquí...'
        };
    }

    /**
     * Obtener editor por ID
     */
    getEditor(id) {
        return this.editors.get(id);
    }

    /**
     * Destruir editor
     */
    async destroyEditor(id) {
        const editor = this.editors.get(id);
        if (editor) {
            await editor.destroy();
            this.editors.delete(id);
        }
    }

    /**
     * Obtener contenido del editor
     */
    getData(id) {
        const editor = this.editors.get(id);
        return editor ? editor.getData() : '';
    }

    /**
     * Establecer contenido del editor
     */
    setData(id, data) {
        const editor = this.editors.get(id);
        if (editor) {
            editor.setData(data);
        }
    }

    /**
     * Destruir todos los editores
     */
    async destroyAll() {
        const promises = [];
        this.editors.forEach((editor, id) => {
            promises.push(this.destroyEditor(id));
        });
        await Promise.all(promises);
    }
}

// Inicializar CKEditor cuando esté disponible
if (typeof ClassicEditor !== 'undefined') {
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            window.ISERCKEditor = ISERCKEditor.init();
        });
    } else {
        window.ISERCKEditor = ISERCKEditor.init();
    }
} else {
    console.info('CKEditor no está cargado. Se omite la inicialización.');
}

// Exportar clase
window.ISERCKEditorClass = ISERCKEditor;

class SolidresMediaManager extends HTMLElement {
    static get observedAttributes() {
        return ['type', 'name', 'single', 'target-id', 'target-element-id'];
    }

    constructor() {
        super();
        this.sources = [];
    }

    request(options) {
        this.classList.add('loading');
        const SRMediaResourceData = 'SRMediaResourceData[0]=' + JSON.stringify({
            type: this.type,
            name: this.name,
            multiple: this.multiple,
        });
        const url = `${Joomla.getOptions('system.paths').baseFull}?option=com_solidres&${SRMediaResourceData}&id=${this.targetId}&format=json&${options.query}`
        const xhr = new XMLHttpRequest();
        xhr.onreadystatechange = () => {
            if (xhr.readyState === 4 && xhr.status === 200) {
                try {
                    const { success, message, data } = JSON.parse(xhr.responseText);

                    if (success) {
                        if (typeof options.success === 'function') {
                            options.success.bind(this)(data);
                        }

                    } else {
                        if (typeof options.error === 'function') {
                            options.error.bind(this)(message);
                        }
                    }

                } catch (e) {
                    console.debug(e)
                }

                this.classList.remove('loading');
            }
        };

        xhr.open(options.method || 'GET', url, true);
        let body = null;

        if (!options.isUpload) {
            xhr.setRequestHeader('Content-Type', 'application/json');
        }

        if (options.method === 'POST' && options.data) {
            body = options.isUpload ? options.data : JSON.stringify(options.data);
        }

        if (options.method !== 'GET') {
            const token = Joomla.getOptions('csrf.token', '');

            if (token) {
                xhr.setRequestHeader('X-CSRF-Token', token);
            }
        }

        xhr.send(body);
    }

    createMediaCard(src) {
        const card = document.createElement('div');
        card.className = 'sr-media-card';

        if (src.image) {
            if (src.image.indexOf('data:image') === 0) {
                card.style.backgroundImage = `url(${src.image})`;

            } else {
                card.setAttribute('data-src', src.image);
                card.style.backgroundImage = `url(${Joomla.getOptions('system.paths').rootFull + src.thumb})`;
            }

            if (src.file) {
                this.cardFileMaps.push({card, file: src.file})
            }
        }

        card.innerHTML = '<button class="sr-btn-remove-media" type="button"><i class="fa fa-trash"></i></button>';
        card.querySelector('.sr-btn-remove-media').addEventListener('click', () => {
            if (this.targetId) {
                this.request({
                    query: `task=media.removeResource`, method: 'POST', data: {src: src.image}, success: () => {
                        card.parentElement.removeChild(card);
                        const removeIndex = this.sources.findIndex(source => (src.image === source.image));

                        if (removeIndex !== -1) {
                            this.sources.splice(removeIndex, 1);
                        }

                        if (!this.multiple) {
                            this.fileInput.value = '';

                            if (!this.sources.length) {
                                this.btnUpload.style.display = '';
                            }
                        }

                        if (this.targetElement) {
                            if (this.multiple) {
                                this.targetElement.value = this.sources.map(src => src.image.substring(src.image.lastIndexOf('/') + 1)).join(',');
                            } else {
                                this.targetElement.value = '';
                            }
                        }

                        this.targetElement?.dispatchEvent(new Event('change'));

                    }, error: message => Joomla?.renderMessages({warning: [message]}),
                });
            } else {
                card.parentElement.removeChild(card);

                if (src.file) {
                    const removeIndex = this.cardFileMaps.findIndex(({file}) => (file === src.file));

                    if (removeIndex !== -1) {
                        this.cardFileMaps.splice(removeIndex, 1);
                    }

                    if (!this.multiple) {
                        this.fileInput.value = '';

                        if (!this.cardFileMaps.length) {
                            this.btnUpload.style.display = '';
                        }
                    }
                }
            }
        }, false);

        return card;
    }

    reOrderSources(sources) {
        this.request({
            query: `task=media.reOrderResources`,
            method: 'POST',
            data: { sources },
            success: data => {
                this.sources = data;

                if (this.targetElement) {
                    const images = this.sources.map(src => src.image.substring(src.image.lastIndexOf('/') + 1));
                    this.targetElement.value = images.join(',');

                    try {
                        const dataSrc = [];
                        const dataSrcs = this.multiple ? JSON.parse(this.targetElement.getAttribute('data-src')) : [this.targetElement.getAttribute('data-src')];

                        for (const image of images) {
                             dataSrc.push(dataSrcs.find(i => i.substring(i.lastIndexOf('/') + 1) === image))
                        }

                        this.targetElement.setAttribute('data-src', JSON.stringify(dataSrc));

                    } catch {}

                }
            },
            error: message => Joomla?.renderMessages({ warning: [message] }),
        });
    }

    connectedCallback() {
        this.cardFileMaps = [];
        this.multiple = this.hasAttribute('single') ? 0 : 1;
        this.name = this.getAttribute('name') || '';
        this.type = (this.getAttribute('type') || 'PROPERTY').toUpperCase();
        this.targetId = parseInt(this.getAttribute('target-id') || '0');
        const targetElementId = this.getAttribute('target-element-id');
        this.targetElement = targetElementId ? this.ownerDocument.getElementById(targetElementId) : null;

        if (!['PROPERTY', 'ROOM_TYPE', 'EXPERIENCE', 'PRO_COUPON', 'EXP_COUPON', 'PRO_EXTRA', 'EXP_EXTRA', 'EXP_CATEGORY', 'EXP_PAYMENT'].includes(this.type)) {
            this.type = 'PROPERTY';
        }

        if (isNaN(this.targetId)) {
            this.targetId = 0;
        }

        const container = document.createElement('div');
        container.className = 'sr-media-container';
        this.appendChild(container);
        // Upload Button
        this.btnUpload = document.createElement('button');
        this.btnUpload.type = 'button';
        this.btnUpload.className = 'sr-btn-upload-media';
        this.btnUpload.innerHTML = '<i class="fa fa-plus"></i>';

        // Append File Input
        this.fileInput = document.createElement('input');
        this.fileInput.name = (this.name ? this.name.replace(/\./g, '_') + '_' : '') + 'SRUploadedMedia[]';
        this.fileInput.type = 'file';
        this.fileInput.multiple = !!this.multiple;
        this.fileInput.accept = 'image/*';
        this.fileInput.style.display = 'none';
        this.fileInput.addEventListener('change', () => {
            const { files } = this.fileInput;

            if (files && files.length) {
                if (this.targetId) {
                    const formData = new FormData();

                    for (const file of files) {
                        formData.append(this.fileInput.name, file);
                    }

                    this.request({
                        isUpload: true,
                        query: 'task=media.uploadResources',
                        method: 'POST',
                        data: formData,
                        success: response => {
                            const { sources, messages } = response[0];

                            if (sources.length) {
                                if (this.multiple) {

                                    for (const src of sources) {
                                        this.sources.push(src);
                                        container.appendChild(this.createMediaCard(src));
                                    }

                                    if (this.targetElement) {
                                        this.targetElement.value = this.sources.map(src => src.image.substring(src.image.lastIndexOf('/') + 1)).join(',');
                                    }

                                } else {
                                    this.btnUpload.style.display = 'none';
                                    const src = sources[0];

                                    if (this.targetElement) {
                                        this.targetElement.value = src.image.substring(src.image.lastIndexOf('/') + 1);
                                    }

                                    this.sources = [src];
                                    container.innerHTML = '';
                                    container.appendChild(this.createMediaCard(src))
                                }

                            } else if (this.targetElement) {
                                this.targetElement.value = '';
                            }

                            this.targetElement?.dispatchEvent(new Event('change'));

                            if (messages.length) {
                                Joomla?.renderMessages({warning: messages});
                            }
                        },
                        error: message => {
                            Joomla?.renderMessages({warning: [message]});
                        }
                    })
                } else {
                    for (const file of files) {
                        const reader = new FileReader();
                        reader.onload = (e) => {
                            const img = new Image();
                            img.onload = () => {
                                const canvas = document.createElement('canvas');
                                const ctx = canvas.getContext('2d');

                                // Set the desired width and height for the resized image
                                const maxWidth = 120;
                                const maxHeight = 120;

                                // Calculate the new dimensions while preserving aspect ratio
                                let width = img.width;
                                let height = img.height;

                                if (width > maxWidth) {
                                    height *= maxWidth / width;
                                    width = maxWidth;
                                }

                                if (height > maxHeight) {
                                    width *= maxHeight / height;
                                    height = maxHeight;
                                }

                                // Set the canvas dimensions to the resized dimensions
                                canvas.width = width;
                                canvas.height = height;

                                // Draw the image onto the canvas
                                ctx.drawImage(img, 0, 0, width, height);
                                container.appendChild(this.createMediaCard({
                                    image: canvas.toDataURL(file.type),
                                    file,
                                }));
                            };

                            img.src = e.target.result;
                        }

                        reader.readAsDataURL(file);
                    }
                }

                if (!this.multiple) {
                    this.btnUpload.style.display = 'none';
                }

            } else if (!this.multiple) {
                this.btnUpload.style.display = '';
            }

            if (this.targetId) {
                // Reset files data
                this.fileInput.value = '';
            }

        }, false);
        this.appendChild(this.fileInput);
        this.form = this.closest('form');

        if (this.form && !this.targetId) {
            const hidden = this.ownerDocument.createElement('input');
            hidden.type = 'hidden';
            hidden.name = 'SRMediaResourceData[]';
            hidden.value = JSON.stringify({type: this.type, name: this.name, multiple: this.multiple});
            this.appendChild(hidden);
            this.form.addEventListener('submit', () => {
                const fileList = new DataTransfer();

                for (const { file } of this.cardFileMaps) {
                    fileList.items.add(file);
                }

                this.fileInput.files = fileList.files;

                if (this.multiple && this.targetElement?.value) {
                    this.targetElement.value = JSON.stringify(this.targetElement.value.split(','))
                }
            });
        }

        this.btnUpload.addEventListener('click', () => this.fileInput.click(), false);
        this.appendChild(this.btnUpload);

        if (this.targetElement) {
            let targetSrc = this.targetElement.getAttribute('data-src');
            if (targetSrc) {
                if (this.multiple) {
                    targetSrc = JSON.parse(targetSrc);
                } else {
                    this.btnUpload.style.display = 'none';
                }

                if (!Array.isArray(targetSrc)) {
                    targetSrc = [targetSrc];
                }

                for (const tSrc of targetSrc) {
                    const src = { image: tSrc, thumb: tSrc };
                    this.sources.push(src);
                    container.appendChild(this.createMediaCard(src));
                }
            }
        } else if (this.targetId) {
            this.request({
                query: `task=media.loadResources`, success(data) {
                    if (data.length) {
                        for (const src of data) {
                            this.sources.push(src);
                            container.appendChild(this.createMediaCard(src));
                        }
                    }
                }, error(message) {
                    this.innerHTML = '<div class="alert alert-warning">' + message + '</div>';
                }
            });
        }
    }
}

// Register the custom element with the browser
customElements.define('solidres-media-manager', SolidresMediaManager);
window.addEventListener('load', function () {
    document.querySelectorAll('solidres-media-manager > .sr-media-container').forEach(container => {
        if (container.parentElement.hasAttribute('single')) {
            return;
        }

        dragula([container], {
            copy: false,
            direction: 'horizontal',
            mirrorContainer: container,
            moves: (el, target, source) => !source.classList.contains('sr-btn-remove-media') && !source.closest('.sr-btn-remove-media'),
        }).on('dragend', _el => {
            const { targetId, sources, cardFileMaps } = container.parentElement;
            const newSources = [];

            if (targetId) {
                container.querySelectorAll('.sr-media-card').forEach(card => {
                    const source = sources.find(({ image }) => image === card.getAttribute('data-src'));

                    if (source) {
                        newSources.push(source.image.split('/').pop());
                    }
                });

                container.parentElement.reOrderSources(newSources);
            } else {
                container.querySelectorAll('.sr-media-card').forEach(cardEl => {
                    const cardFile = cardFileMaps.find(({ card }) => card === cardEl);

                    if (cardFile) {
                        newSources.push(cardFile);
                    }
                });

                container.parentElement.cardFileMaps = newSources;
            }
        });
    })
});
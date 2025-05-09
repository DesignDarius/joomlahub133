Solidres = window.Solidres || {};
Solidres.InlineEdit = function (selector, config) {
    config = config || {};
    const createLoader = function () {
        const span = document.createElement('span');
        Object.assign(span.style, {
            backgroundImage: 'url(' + Joomla.getOptions('system.paths').root + '/media/com_solidres/assets/images/ajax-loader2.gif)',
            backgroundRepeat: 'no-repeat',
            backgroundSize: 'contain',
            width: '25px',
            height: '14px',
            display: 'inline-block',
        })

        return span;
    };
    const editable = function (element) {
        const {type, name, value} = element.dataset;
        const container = document.createElement('div');
        element.draggable = false;
        container.className = 'sr-inline-edit-container input-group d-none';
        const node = document.createElement(['number', 'text'].includes(type) ? 'input' : type);
        let isLoading = false;
        node.name = name;
        switch (type) {
            case 'select':
                if (Array.isArray(config.source)) {
                    for (const option of config.source) {
                        const opt = document.createElement('option');
                        opt.value = option.value;
                        opt.innerText = option.text;

                        if (value == option.value) {
                            opt.selected = true;
                        }

                        node.appendChild(opt);
                    }
                }

                node.classList.add('form-select', 'form-select-sm');
                break;

            case 'input':
            case 'number':
            case 'text':
            case 'textarea':

                if (typeof value !== 'undefined') {
                    node.value = value;
                }

                if (type === 'number') {
                    node.type = 'number';
                    node.min = '0';
                }

                node.classList.add('form-control', 'form-control-sm');
                break;
        }

        container.appendChild(node);
        // Button Close
        const btnClose = document.createElement('button');
        btnClose.type = 'button';
        btnClose.className = 'btn btn-sm btn-danger';
        btnClose.innerHTML = '<span class="icon-times" aria-hidden="true"></span>';
        btnClose.addEventListener('click', e => {
            e.preventDefault();
            container.classList.add('d-none');
        }, false);

        // Button OK
        const btnOk = document.createElement('button');
        btnOk.type = 'button';
        btnOk.className = 'btn btn-sm btn-primary';
        btnOk.innerHTML = '<span class="icon-save" aria-hidden="true"></span>';
        btnOk.addEventListener('click', e => {
            e.preventDefault();

            if (typeof config.url === 'string') {
                isLoading = true;
                const loader = createLoader();
                element.parentNode.insertBefore(loader, element);
                const onDone = () => {
                    isLoading = false;
                    loader.parentNode.removeChild(loader);
                };
                window.Joomla?.request({
                    // Find the action url associated with the form - we need to add the token to this
                    url: config.url,
                    method: 'POST',
                    data: new URLSearchParams({...element.dataset, value: node.value}).toString(),
                    onSuccess: response => {
                        onDone();
                        let text = node.value;

                        if (type === 'select' && Array.isArray(config.source)) {
                            for (const option of config.source) {
                                if (option.value == node.value) {
                                    text = option.text;
                                    break;
                                }
                            }
                        }

                        if (typeof response === 'string' && ['{', '['].includes(response.trim()[0])) {
                            try {
                                response = JSON.parse(response);
                            } catch {
                            }
                        }

                        if (response.success)
                        {
                            element.innerText = text;
                        }

                        if (typeof config.success === 'function') {

                            // Move container to body to reject the callback reset element content
                            document.body.appendChild(container);
                            config.success.bind(element)(response, element);
                            setTimeout(() => {
                                // Revert container
                                element.appendChild(container);
                            }, 0);
                        }
                    },
                    onError: () => {
                        onDone();
                        if (typeof config.error === 'function') {
                            config.error();
                        }
                    }
                });
            }

            btnClose.click();
        }, false);

        container.appendChild(btnOk);
        container.appendChild(btnClose);
        element.style.position = 'relative';
        element.style.overflow = 'visible';
        Object.assign(container.style, {
            position: 'absolute', left: '0px', top: '100%', width: '230px', zIndex: '100',
        })
        element.appendChild(container);
        element.addEventListener('click', e => {
            e.preventDefault();

            if (!isLoading && e.target !== container && !container.contains(e.target)) {
                container.classList.toggle('d-none');
            }
        }, false);

        if (typeof config.initContainer === 'function') {
            config.initContainer(container);
        }

        document.addEventListener('keydown', e => {
            if (e.key === 'Enter' && !container.classList.contains('d-none')) {
                btnOk.click();
            } else if (e.key === 'Escape') {
                btnClose.click();
            }
        }, false);
        document.addEventListener('click', e => {
            const { target } = e;

            if (
                !target
                || target !== container
                && target !== container.parentElement
                && !container.contains(target)
            ) {
                container.classList.add('d-none');
            }
        }, false);
    }

    document.querySelectorAll(selector).forEach(editable);
}
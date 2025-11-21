/* exported editForm, deleteForm */
document.addEventListener('alpine:init', () => {
  Alpine.data('formsAdmin', () => ({
    forms: [],
    sites: [],
    form: {
      id: null,
      name: '',
      site_id: '',
      emailOnSubmit: false,
      sendOnSubmit: '',
      urlOnOk: '',
      urlOnError: ''
    },

    init() {
      // load sites and forms (parallel where possible)
      this.loadSites();
      this.loadForms();
    },

    async fetchJson(path, opts = {}) {
      // build headers concisely and attach token if present
      const headers = Object.assign({ Accept: 'application/json' }, opts.headers || {});
      try {
        const token = localStorage.getItem('api_token');
        if (token) headers['Authorization'] = 'Bearer ' + token;
      } catch (e) {}

      const method = opts.method || (opts.json ? 'POST' : 'GET');
      const body = opts.json ? JSON.stringify(opts.json) : opts.body;
      if (opts.json) headers['Content-Type'] = 'application/json';

      const resp = await fetchWithAuth(path, Object.assign({}, opts, { method, headers, body }));
      const txt = await resp.text();
      let data = null;
      try { data = txt ? JSON.parse(txt) : null; } catch (e) { data = txt; }

      if (!resp.ok) throw { status: resp.status, data };
      return data;
    },

    showAlert(message, type = 'success') {
      const placeholder = document.getElementById('alertPlaceholder');
      if (!placeholder) return;
      const div = document.createElement('div');
      div.className = `alert alert-${type} alert-dismissible`;
      div.role = 'alert';
      div.innerHTML = message + '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
      placeholder.appendChild(div);
    },

    async loadSites() {
      try {
        this.sites = await this.fetchJson('/api/sites');
        const params = new URLSearchParams(window.location.search);
        const filterSiteId = params.get('site_id');
        if (filterSiteId) this.form.site_id = filterSiteId;
      } catch (e) {
        console.error(e);
        this.showAlert('Failed to load sites', 'danger');
      }
    },

    async loadForms() {
      try {
        this.forms = await this.fetchJson('/api/forms');
      } catch (e) {
        console.error(e);
        this.showAlert('Failed to load forms', 'danger');
      }
    },

    openFormModal(form) {
      const params = new URLSearchParams(window.location.search);
      const filterSiteId = params.get('site_id') || '';

      this.form = form ? Object.assign({}, form) : {
        id: null,
        name: '',
        site_id: filterSiteId,
        emailOnSubmit: false,
        sendOnSubmit: '',
        urlOnOk: '',
        urlOnError: ''
      };

      document.getElementById('formModalTitle').textContent = form ? 'Edit Form' : 'New Form';
      new bootstrap.Modal(document.getElementById('formModal')).show();
    },

    async editForm(id) {
      try {
        // try to fetch single form first, fallback to list if endpoint isn't available
        let f;
        try {
          f = await this.fetchJson(`/api/forms/${id}`);
        } catch (e) {
          // if the single-form endpoint isn't available, fall back to fetching the list and finding it
          const list = await this.fetchJson('/api/forms');
          f = list.find(x => String(x.id) === String(id));
        }
        this.openFormModal(f);
      } catch (e) {
        console.error(e);
        this.showAlert('Failed to fetch form', 'danger');
      }
    },

    async deleteForm(id) {
      if (!confirm('Delete form ' + id + '?')) return;
      try {
        await this.fetchJson(`/api/forms/${id}`, { method: 'DELETE' });
        this.showAlert('Deleted', 'success');
        this.loadForms();
      } catch (e) {
        console.error(e);
        this.showAlert('Delete failed', 'danger');
      }
    },

    async save() {
      try {
        const id = this.form.id;
        const payload = {
          name: this.form.name || null,
          site_id: this.form.site_id || null,
          emailOnSubmit: !!this.form.emailOnSubmit,
          sendOnSubmit: this.form.sendOnSubmit || null,
          urlOnOk: this.form.urlOnOk || null,
          urlOnError: this.form.urlOnError || null
        };

        const path = id ? `/api/forms/${id}` : '/api/forms';
        const method = id ? 'PUT' : 'POST';

        await this.fetchJson(path, { method, json: payload });
        this.showAlert(id ? 'Updated' : 'Created', 'success');

        await this.loadForms();

        const modalEl = document.getElementById('formModal');
        const modal = bootstrap.Modal.getInstance(modalEl);
        if (modal) modal.hide();
      } catch (err) {
        console.error(err);
        const msg = err && err.data && err.data.error ? err.data.error : 'Save failed';
        this.showAlert(msg, 'danger');
      }
    }
  }));
});

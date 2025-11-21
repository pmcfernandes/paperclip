/* exported openModal, removeSite */
document.addEventListener('alpine:init', () => {
  Alpine.data('sitesAdmin', () => ({
    sites: [],
    form: {
      id: null,
      slug: '',
      name: '',
      domain: '',
      webhook_url: '',
      webhook_token: ''
    },

    init() {
      this.loadSites();
    },

    async fetchJson(path, opts = {}) {
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
      } catch (e) {
        console.error(e);
        this.showAlert('Failed to load sites', 'danger');
      }
    },

    openModal(site) {
      this.form = site
        ? Object.assign({}, site)
        : { id: null, slug: '', name: '', domain: '', webhook_url: '', webhook_token: '' };

      document.getElementById('siteModalTitle').textContent = site ? 'Edit Site' : 'New Site';
      new bootstrap.Modal(document.getElementById('siteModal')).show();
    },

    async save() {
      try {
        const id = this.form.id;
        const payload = {
          name: this.form.name || null,
          domain: this.form.domain || null,
          webhook_url: this.form.webhook_url || null,
          webhook_token: this.form.webhook_token || null
        };

        const path = id ? `/api/sites/${id}` : '/api/sites';
        const method = id ? 'PUT' : 'POST';

        await this.fetchJson(path, { method, json: payload });
        this.showAlert(id ? 'Updated' : 'Created', 'success');

        await this.loadSites();

        const modalEl = document.getElementById('siteModal');
        const modal = bootstrap.Modal.getInstance(modalEl);
        if (modal) modal.hide();
      } catch (err) {
        console.error(err);
        const msg = err && err.data && err.data.error ? err.data.error : 'Save failed';
        this.showAlert(msg, 'danger');
      }
    },

    async removeSite(id) {
      if (!confirm('Delete site ' + id + '?')) return;

      try {
        await this.fetchJson(`/api/sites/${id}`, { method: 'DELETE' });
        this.showAlert('Deleted', 'success');
        this.loadSites();
      } catch (e) {
        console.error(e);
        this.showAlert('Delete failed', 'danger');
      }
    }
  }));
});

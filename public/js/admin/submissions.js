/* exported formatHeader */
document.addEventListener('alpine:init', () => {
  Alpine.data('submissionsAdmin', () => ({
    rows: [],
    otherKeys: [],
    filterSubmitId: '',
    selected: null,

    init() {
      const params = new URLSearchParams(window.location.search);
      if (params.get('submit_id')) this.filterSubmitId = params.get('submit_id');
      this.loadSubmissions();
    },

    formatHeader(k) {
      return String(k).replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase());
    },

    formatDateTime(s) {
      if (!s) return '';
      try {
        const d = new Date(s);
        if (isNaN(d)) return String(s);
        const pad = n => String(n).padStart(2, '0');
        return `${pad(d.getDate())}-${pad(d.getMonth() + 1)}-${pad(d.getFullYear())} ${pad(d.getHours())}:${pad(d.getMinutes())}`;
      } catch (e) {
        return String(s);
      }
    },

    formatValue(v) {
      if (v === null) return '';
      if (typeof v === 'object') return JSON.stringify(v, null, 2);
      if (typeof v === 'string' && /^\d{4}-\d{2}-\d{2}T/.test(v)) return this.formatDateTime(v);
      return String(v);
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

    showAlert(message, type = 'danger') {
      const placeholder = document.getElementById('alertPlaceholder');
      if (!placeholder) return;
      const div = document.createElement('div');
      div.className = `alert alert-${type} alert-dismissible`;
      div.role = 'alert';
      div.innerHTML = message + '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
      placeholder.appendChild(div);
    },

    async loadSubmissions() {
      try {
        const params = new URLSearchParams();
        const pageParams = new URLSearchParams(window.location.search);
        const formId = pageParams.get('form_id');
        if (formId) params.set('form_id', formId);
        if (this.filterSubmitId) params.set('submit_id', this.filterSubmitId);

        const url = '/api/forms/submissions' + (params.toString() ? ('?' + params.toString()) : '');
        const rows = await this.fetchJson(url);
        this.rows = rows || [];

        const core = new Set(['submit_id', 'submitted_at', 'id']);
        const keys = new Set();
        this.rows.forEach(r => Object.keys(r).forEach(k => { if (!core.has(k)) keys.add(k); }));
        this.otherKeys = Array.from(keys);
      } catch (e) {
        console.error(e);
        this.showAlert('Failed to load submissions', 'danger');
      }
    },

    view(row) {
      this.selected = row;
      const modalEl = document.getElementById('submissionModal');
      const modal = new bootstrap.Modal(modalEl);
      modal.show();
    }
  }));
});

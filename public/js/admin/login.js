document.addEventListener('alpine:init', () => {
  Alpine.data('loginForm', () => ({
    usernameOrEmail: '',
    password: '',
    message: '',

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

    async submit() {
      this.message = '';
      const payload = { username: this.usernameOrEmail.trim(), password: this.password };
      try {
        const body = await this.fetchJson('/api/users/login', { method: 'POST', json: payload });
        const token = body && body.token ? body.token : null;
        localStorage.setItem('api_token', token);
        window.location.href = '/admin/sites';
      } catch (err) {
        const text = err && err.data && err.data.error ? err.data.error : 'Login failed';
        this.message = `<div class="alert alert-danger">${text}</div>`;
      }
    }
  }))
})

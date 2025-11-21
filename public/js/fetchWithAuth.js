// Lightweight fetch wrapper that injects Authorization header from localStorage
(function(){
  window.fetchWithAuth = async function(path, opts = {}){
    opts = opts || {};
    opts.headers = Object.assign({}, opts.headers || {});
    if (!opts.headers['Accept']) opts.headers['Accept'] = 'application/json';
    try {
      const token = localStorage.getItem('api_token');
      if (token) opts.headers['Authorization'] = 'Bearer ' + token;
    } catch (e) {}

    if (opts.json) {
      opts.method = opts.method || 'POST';
      opts.headers['Content-Type'] = 'application/json';
      opts.body = JSON.stringify(opts.json);
      delete opts.json;
    }

    return fetch(path, opts);
  };
})();

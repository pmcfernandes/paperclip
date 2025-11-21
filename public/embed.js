(function(window, document){
  'use strict';

  // Simple embed loader for Paperclip forms.
  // Usage: include this script and call `PaperclipForm.embed({ el: '#my-form', slug: 'contact', endpoint: '/forms' })`
  // or use the automatic data-* initialization: <div class="paperclip-form" data-slug="contact"></div>

  function ajaxGet(url, cb) {
    var xhr = new XMLHttpRequest();
    xhr.open('GET', url, true);
    xhr.onreadystatechange = function() {
      if (xhr.readyState !== 4) return;
      cb(xhr.status, xhr.responseText);
    };
    xhr.send(null);
  }

  function ajaxPost(url, dataObj, cb) {
    var xhr = new XMLHttpRequest();
    xhr.open('POST', url, true);
    xhr.setRequestHeader('Accept', 'application/json');
    // use application/x-www-form-urlencoded for simple forms
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    var body = Object.keys(dataObj).map(function(k){
      return encodeURIComponent(k) + '=' + encodeURIComponent(dataObj[k]);
    }).join('&');
    xhr.onreadystatechange = function(){
      if (xhr.readyState !== 4) return;
      var json = null;
      try { json = JSON.parse(xhr.responseText); } catch(e){ json = xhr.responseText; }
      cb(xhr.status, json);
    };
    xhr.send(body);
  }

  function findParentRoot(el) {
    while (el && el.nodeType === 1) {
      if (el.classList && el.classList.contains('paperclip-form-root')) return el;
      el = el.parentNode;
    }
    return null;
  }

  var PaperclipForm = {
    embed: function(opts) {
      opts = opts || {};
      var root = document.querySelector(opts.el || '.paperclip-form');
      if (!root) return;

      var slug = opts.slug || root.getAttribute('data-slug');
      if (!slug) return;

      var endpoint = opts.endpoint || (root.getAttribute('data-endpoint') || '/forms');

      // load the HTML fragment from the server (rendered Twig) and insert into root
      var url = endpoint + '/' + encodeURIComponent(slug);
      ajaxGet(url, function(status, html){
        if (status >= 200 && status < 300) {
          // make container for form and mark root
          var container = document.createElement('div');
          container.className = 'paperclip-form-root';
          container.innerHTML = html;
          root.innerHTML = '';
          root.appendChild(container);

          // wire up submit handler
          var form = container.querySelector('form');
          if (form) {
            form.addEventListener('submit', function(e){
              e.preventDefault();
              var formData = {};
              // include fields
              Array.prototype.slice.call(form.elements).forEach(function(el){
                if (!el.name) return;
                if (el.type === 'checkbox' || el.type === 'radio') {
                  if (!el.checked) return;
                }
                if (el.tagName.toLowerCase() === 'button') return;
                formData[el.name] = el.value;
              });

              // post to the same action
              ajaxPost(form.getAttribute('action') || url + '/submit', formData, function(status, resp){
                if (status >= 200 && (status === 201 || status === 200)) {
                  // success — show a simple message or redirect if provided
                  if (resp && resp.success) {
                    container.innerHTML = '<div class="paperclip-success">Thanks — your submission was received.</div>';
                  } else if (resp && resp.redirect) {
                    window.location.href = resp.redirect;
                  } else {
                    container.innerHTML = '<div class="paperclip-success">Thanks — your submission was received.</div>';
                  }
                } else if (status === 403) {
                  container.insertAdjacentHTML('beforeend', '<div class="paperclip-error">Security token invalid. Please reload the page and try again.</div>');
                } else {
                  container.insertAdjacentHTML('beforeend', '<div class="paperclip-error">Submission failed. Please try again later.</div>');
                }
              });
            });
          }

        } else {
          root.innerHTML = '<div class="paperclip-error">Failed to load form</div>';
        }
      });
    },

    autoInit: function(){
      var nodes = document.querySelectorAll('.paperclip-form');
      Array.prototype.forEach.call(nodes, function(n){
        var slug = n.getAttribute('data-slug');
        if (slug) PaperclipForm.embed({el: n, slug: slug});
      });
    }
  };

  // expose to global
  window.PaperclipForm = PaperclipForm;
  // auto-init on DOMContentLoaded
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function(){ PaperclipForm.autoInit(); });
  } else {
    PaperclipForm.autoInit();
  }

})(window, document);

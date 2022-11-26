class cPageclip {
  constructor(apiKey) {
    this.apiKey = apiKey;
  }
}

cPageclip.prototype.getData = function (form) {
  const formToJSON = elements => [].reduce.call(elements, (data, element) => {
    if (element.name !== '') {
      data[element.name] = element.value;
    }
    return data;
  }, {});

  return formToJSON(form.elements);
};

cPageclip.prototype.form = function (form, options) {
  var _options = {};
  Object.assign(_options, {
    onSubmit: function (event) { },
    onResponse: function (error, response) { },
    successTemplate: '<span>Thank you!</span>'
  }, options);

  this.options = _options;
  this.form = form;
  return this;
};

cPageclip.prototype.send = function (key, slug, data, callback) {
  var url = '../pageclip.php?key=' + encodeURIComponent(key) + '&slug=' + encodeURIComponent(slug);
  var apiKey = this.apiKey;

  var _form = this.form;
  var _options = this.options;
  var d = data || this.getData(_form);
  _form.removeAttribute('action', '');
  _form.removeAttribute('method');
  _form.addEventListener("submit", function (e) {
    e.preventDefault();

    var ret = _options.onSubmit(e) || true;

    if (ret || typeof ret === 'undefined') {
      fetch(url, {
        method: 'POST',
        body: JSON.stringify(d),
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded',
          'Authorization': 'Bearer ' + apiKey
        },
      }).then(function (response) {
        return response.json();
      })
        .then(function (data) {
          if (typeof callback === 'undefined'
            ? _options.onResponse(null, data)
            : callback(null, data));
        })
        .catch(function (err) {
          if (typeof callback === 'undefined'
            ? _options.onResponse(err, {})
            : callback(err, {}));
        });
    }

    return false;
  });

  _form.submit();
};

var Pageclip = new cPageclip('xpto');

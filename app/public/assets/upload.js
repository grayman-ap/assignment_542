(function () {
  var drop = document.getElementById('dropzone');
  var input = document.getElementById('document');
  var pick = document.getElementById('pickBtn');
  var name = document.getElementById('fileName');
  if (!drop || !input || !pick) { return; }

  function show() {
    var f = input.files[0];
    name.textContent = f ? f.name + ' (' + (f.size / 1024).toFixed(0) + ' KB)' : '';
  }
  pick.addEventListener('click', function () { input.click(); });
  input.addEventListener('change', show);
  ['dragenter', 'dragover'].forEach(function (ev) {
    drop.addEventListener(ev, function (e) { e.preventDefault(); drop.classList.add('drag'); });
  });
  ['dragleave', 'drop'].forEach(function (ev) {
    drop.addEventListener(ev, function (e) { e.preventDefault(); drop.classList.remove('drag'); });
  });
  drop.addEventListener('drop', function (e) {
    e.preventDefault();
    if (e.dataTransfer.files.length) { input.files = e.dataTransfer.files; show(); }
  });
})();

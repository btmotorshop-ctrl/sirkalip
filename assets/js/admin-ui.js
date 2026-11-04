// Yönetim paneli kabuğu: menü arama, grup aç/kapa, mobil çekmece, kullanıcı menüsü
(function () {
  var side = document.getElementById('uiSide'), nav = document.getElementById('uiNav'),
      input = document.getElementById('uiSearch'), empty = document.getElementById('uiEmpty');
  if (!side || !nav) return;
  var groups = [].slice.call(nav.querySelectorAll('.ui-group')), solos = [].slice.call(nav.querySelectorAll('.ui-solo'));

  // Grup aç/kapa
  nav.addEventListener('click', function (e) {
    var b = e.target.closest('[data-toggle]'); if (!b) return;
    b.parentNode.classList.toggle('is-open');
  });

  // Menüde ara
  function norm(s) { return (s || '').toLocaleLowerCase('tr').trim(); }
  function filter() {
    var q = norm(input.value), shown = 0;
    nav.classList.toggle('is-searching', !!q);
    groups.forEach(function (g) {
      var any = false;
      [].forEach.call(g.querySelectorAll('.ui-link'), function (a) {
        var ok = !q || a.getAttribute('data-k').indexOf(q) > -1;
        a.style.display = ok ? '' : 'none'; if (ok) any = true;
      });
      g.style.display = any ? '' : 'none';
      if (q) g.classList.toggle('is-open', any);
      if (any) shown++;
    });
    solos.forEach(function (a) {
      var ok = !q || a.getAttribute('data-k').indexOf(q) > -1; a.style.display = ok ? '' : 'none'; if (ok) shown++;
    });
    empty.style.display = shown ? 'none' : 'block';
  }
  input.addEventListener('input', filter);
  input.addEventListener('keydown', function (e) {
    if (e.key === 'Enter') { var a = nav.querySelector('.ui-link:not([style*="none"])'); if (a && input.value) location.href = a.href; }
    if (e.key === 'Escape') { input.value = ''; filter(); input.blur(); }
  });
  document.addEventListener('keydown', function (e) {
    var tag = (document.activeElement && document.activeElement.tagName) || '';
    if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') { e.preventDefault(); document.body.classList.add('ui-open'); input.focus(); input.select(); }
    else if (e.key === '/' && !/INPUT|TEXTAREA|SELECT/.test(tag) && !document.activeElement.isContentEditable) { e.preventDefault(); input.focus(); }
  });

  // Mobil çekmece
  document.addEventListener('click', function (e) {
    if (e.target.closest('[data-ui-open]')) document.body.classList.add('ui-open');
    else if (e.target.closest('[data-ui-close]')) document.body.classList.remove('ui-open');
  });

  // Kullanıcı menüsü
  var user = document.getElementById('uiUser');
  if (user) {
    user.querySelector('.ui-avatar').addEventListener('click', function (e) { e.stopPropagation(); user.classList.toggle('is-open'); });
    document.addEventListener('click', function (e) { if (!user.contains(e.target)) user.classList.remove('is-open'); });
  }

  // Etkin bağlantıyı görünür alana kaydır
  var on = nav.querySelector('.ui-link.is-on, .ui-solo.is-on'); if (on && on.scrollIntoView) on.scrollIntoView({ block: 'center' });
})();

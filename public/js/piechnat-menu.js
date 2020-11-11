addEventListener('DOMContentLoaded', () => {

  try{var forceReflowJS=(forceReflowJS=function(a){"use strict";void a.offsetHeight}).call.bind(
  Object.getOwnPropertyDescriptor(HTMLElement.prototype,"offsetHeight").get)}catch(e){}//anonyco
  const $=(s,o=document)=>o.querySelector(s),$$=(s,o=document)=>[].slice.call(o.querySelectorAll(s));

  const navbarCntnr = $('#navigation-bar > .container'); if (!navbarCntnr) return;
  const navbar = navbarCntnr.parentNode, subMenus = $$('ul ul', navbar), CLOSE = 'js_close';
  const APP_ID = 'piechnat_total_menu', STICKY = 'sticky', DISABLE_ANIM = 'disable-anim';
  const menuBtn = $('.menu-wrapper > input', navbar), bluredBg = $('.blurred-bg', navbar);
  if (menuBtn && bluredBg) bluredBg.onclick = () => menuBtn.click();

  const openMenus = (sessionStorage.getItem(APP_ID) || '').split(','); 
  addEventListener('beforeunload', () => sessionStorage.setItem(APP_ID, openMenus.join(',')));

  let sideMode = false, resizeTmt;
  $$('a[href="#"]', navbar).forEach(o => o.onclick = e => e.preventDefault());

  const handleResize = () => { /* on Safari the content is without quotes */
    sideMode = (getComputedStyle(navbar, '::before').content.indexOf('SIDE') > -1);
    document.documentElement.style.setProperty(
      '--navigation-bar-height', navbarCntnr.getBoundingClientRect().height + 'px'); 
    clearTimeout(resizeTmt);
    resizeTmt = setTimeout(() => {
      subMenus.forEach(node => node.style.transform = '');
      if (!sideMode) {
        subMenus.forEach(node => {
          let x = 100, ulRect = node.getBoundingClientRect();
          let y = Math.min(0, document.documentElement.clientHeight - ulRect.bottom);
          if (node.parentNode.parentNode.parentNode.tagName.toUpperCase() === 'NAV') x = 0;
          if (ulRect.right > document.documentElement.clientWidth) x *= -1;
          node.style.transform = `translate(${x}%, ${y}px)`;
        });
      }
    }, 200);
  };
  addEventListener('resize', handleResize, false);
  addEventListener('load', handleResize, false);
  handleResize();
  
  subMenus.forEach((node, index) => {
    const link = $('a', node.parentNode);
    if (link) {
      if (!openMenus[index]) node.parentNode.classList.add(CLOSE);
      link.addEventListener('click', e => {
        if (sideMode) {
          e.preventDefault();
          if (node.parentNode.classList.contains(CLOSE)) {
            node.style.overflow = 'hidden';
            node.style.height = node.scrollHeight + 'px';
            node.parentNode.classList.remove(CLOSE);
            openMenus[index] = 1;
          } else {
            node.style.height = node.getBoundingClientRect().height + 'px';
            node.parentNode.classList.add(CLOSE);
            forceReflowJS(node);
            node.style.height = '';
            delete openMenus[index];
          }
        }
      });
      node.addEventListener('transitionend', () => {
        if (sideMode) {
          if (node.parentNode.classList.contains(CLOSE)) {
            node.classList.add(DISABLE_ANIM);
            subMenus.forEach((n, i) => { if (node.contains(n)) {
              n.parentNode.classList.add(CLOSE); 
              delete openMenus[i];
            }});
            forceReflowJS(node);
            node.classList.remove(DISABLE_ANIM);
          } else {
            node.style.height = node.style.overflow = '';
          }
        }
      });
    }
  });

  (new IntersectionObserver((elms) => { 
    if (elms[0].isIntersecting) navbar.classList.remove(STICKY); else navbar.classList.add(STICKY);
  }, {threshold: 1})).observe(navbar);

});

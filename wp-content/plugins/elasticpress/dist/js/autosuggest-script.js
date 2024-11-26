!function(){"use strict";var e=window.wp.hooks;window.addEventListener("load",(()=>{"undefined"!==typeof window.epDataFilter&&(0,e.addFilter)("ep.Autosuggest.data","ep/epDatafilter",window.epDatafilter),"undefined"!==typeof window.epAutosuggestItemHTMLFilter&&(0,e.addFilter)("ep.Autosuggest.itemHTML","ep/epAutosuggestItemHTMLFilter",window.epAutosuggestItemHTMLFilter),"undefined"!==typeof window.epAutosuggestListItemsHTMLFilter&&(0,e.addFilter)("ep.Autosuggest.listHTML","ep/epAutosuggestListItemsHTMLFilter",window.epAutosuggestListItemsHTMLFilter),"undefined"!==typeof window.epAutosuggestQueryFilter&&(0,e.addFilter)("ep.Autosuggest.query","ep/epAutosuggestQueryFilter",window.epAutosuggestQueryFilter),"undefined"!==typeof window.epAutosuggestElementFilter&&(0,e.addFilter)("ep.Autosuggest.element","ep/epAutosuggestElementFilter",window.epAutosuggestElementFilter)}));var t={randomUUID:"undefined"!==typeof crypto&&crypto.randomUUID&&crypto.randomUUID.bind(crypto)};let s;const o=new Uint8Array(16);function n(){if(!s&&(s="undefined"!==typeof crypto&&crypto.getRandomValues&&crypto.getRandomValues.bind(crypto),!s))throw new Error("crypto.getRandomValues() not supported. See https://github.com/uuidjs/uuid#getrandomvalues-not-supported");return s(o)}const r=[];for(let e=0;e<256;++e)r.push((e+256).toString(16).slice(1));function a(e,t=0){return r[e[t+0]]+r[e[t+1]]+r[e[t+2]]+r[e[t+3]]+"-"+r[e[t+4]]+r[e[t+5]]+"-"+r[e[t+6]]+r[e[t+7]]+"-"+r[e[t+8]]+r[e[t+9]]+"-"+r[e[t+10]]+r[e[t+11]]+r[e[t+12]]+r[e[t+13]]+r[e[t+14]]+r[e[t+15]]}var i=function(e,s,o){if(t.randomUUID&&!s&&!e)return t.randomUUID();const r=(e=e||{}).random||(e.rng||n)();if(r[6]=15&r[6]|64,r[8]=63&r[8]|128,s){o=o||0;for(let e=0;e<16;++e)s[o+e]=r[e];return s}return a(r)};const u=(e,t)=>{let s=null;return function(...o){const n=this;window.clearTimeout(s),s=window.setTimeout((()=>{e.apply(n,o)}),t)}},c=(e,t,s)=>e.replace(new RegExp((e=>e.replace(/[.*+?^${}()|[\]\\]/g,"\\$&"))(t),"g"),s),l=e=>e.replace(/\\([\s\S])|(")/g,"&quot;"),d=(e,t)=>{for(;(e=e.parentElement)&&!e.classList.contains(t););return e},p=t=>{const s=i().replaceAll("-","");return(0,e.applyFilters)("ep.requestId",t+s)},{epas:g}=window;function f(e,t){t.setAttribute("aria-activedescendant",e)}function h(e,t){!function(e){const t=new CustomEvent("ep-autosuggest-click",{detail:e});window.dispatchEvent(t);let s=null;"function"===typeof window?.gtag?s=window.gtag:"function"===typeof window?.dataLayer?.push&&(s=window.dataLayer.push),e.searchTerm&&1===parseInt(g.triggerAnalytics,10)&&s&&s("event",`click - ${e.searchTerm}`,{event_category:"EP :: Autosuggest",event_label:e.url,transport_type:"beacon"})}({searchTerm:e,url:t}),window.location.href=t}function y(t,s){if("navigate"===g.action){return(0,e.applyFilters)("ep.Autosuggest.navigateCallback",h)(t.value,s.dataset.url)}return function(e,t){e.value=t}(t,s.innerText),function(e){e.closest("form").submit()}(t)}function m(e,t,{query:s}){return c(s,t,e)}async function w(t,s){const o={body:t,method:"POST",mode:"cors",headers:{"Content-Type":"application/json; charset=utf-8"}};g?.http_headers&&"object"===typeof g.http_headers&&Object.keys(g.http_headers).forEach((e=>{o.headers[e]=g.http_headers[e]})),g.addSearchTermHeader&&(o.headers["EP-Search-Term"]=encodeURI(s));const n=p(g?.requestIdBase||"");n&&(o.headers["X-ElasticPress-Request-ID"]=n);try{const t=await fetch(g.endpointUrl,(0,e.applyFilters)("ep.Autosuggest.fetchOptions",o));if(!t.ok)throw Error(t.statusText);const n=await t.json();return(0,e.applyFilters)("ep.Autosuggest.data",n,s)}catch(e){return console.error(e),e}}function A(t,s){let o="";const{value:n}=s,r=d(s,"ep-autosuggest-container").querySelector(".ep-autosuggest"),a=r.querySelector(".autosuggest-list");for(;a.firstChild;)a.removeChild(a.firstChild);t.length>0?r.style="display: block;":r.style="display: none;";const i=t.length;for(let s=0;i>s;++s){const r=t[s],a=r._source.post_title,i=r._source.permalink,u=l(a),c=n.trim().split(" ");let d=u;if(g.highlightingEnabled){const e=new RegExp(`\\b(${c.join("|")})`,"gi");d=d.replace(e,(e=>`<${g.highlightingTag} class="${g.highlightingClass} ep-autosuggest-highlight">${e}</${g.highlightingTag}>`))}let p=`<li class="autosuggest-item" role="option" aria-selected="false" id="autosuggest-option-${s}">\n\t\t\t\t<a href="${i}" class="autosuggest-link" data-search="${u}" data-url="${i}"  tabindex="-1">\n\t\t\t\t\t${d}\n\t\t\t\t</a>\n\t\t\t</li>`;p=(0,e.applyFilters)("ep.Autosuggest.itemHTML",p,r,s,n),o+=p}return o=(0,e.applyFilters)("ep.Autosuggest.listHTML",o,t,s),a.innerHTML=o,a.addEventListener("click",(e=>{e.preventDefault();const t=e.target.closest(".autosuggest-link");a.contains(t)&&y(s,t)})),f("",s),!0}function v(){const e=document.querySelectorAll(".autosuggest-list"),t=document.querySelectorAll(".ep-autosuggest"),s=document.querySelectorAll(".ep-autosuggest-container [aria-activedescendant]");return e.forEach((e=>{for(;e.firstChild;)e.removeChild(e.firstChild)})),t.forEach((e=>{e.style="display: none;"})),s.forEach((e=>f("",e))),!0}function E(e,t){const s=t.closest("form");e?s.classList.add("is-loading"):s.classList.remove("is-loading")}g.endpointUrl&&""!==g.endpointUrl&&(!function(){const t=[g.defaultSelectors,g.selector].filter(Boolean).join(",");if(!t)return;let s,o;const n=[38,40,13],r=e=>{if(!n.includes(e.keyCode))return;const t=e.target,s=d(t,"ep-autosuggest-container").querySelector(".autosuggest-list").children,r=()=>Array.from(s).findIndex((e=>e.classList.contains("selected"))),a=()=>{Array.from(s).forEach((e=>{e.classList.remove("selected"),e.setAttribute("aria-selected","false")}))},i=()=>{if(o>=0){const e=s[o];e.classList.add("selected"),e.setAttribute("aria-selected","true"),f(e.id,t)}};switch(e.keyCode){case 38:o=o-1>=0?o-1:0,a();break;case 40:if("undefined"===typeof o)o=0;else{const e=r();s[e+1]&&(o=e+1,a())}break;case 13:s[o]?.classList.contains("selected")&&y(t,s[o].querySelector(".autosuggest-link"))}s[o]&&s[o].classList.contains("autosuggest-item")?i():a(),38===e.keyCode&&e.preventDefault()};function a(e){const t=new FormData(e);return t.has("post_type")?t.getAll("post_type").slice(-1):t.has("post_type[]")?t.getAll("post_type[]"):[]}const i=u((async t=>{const s=function(){if("undefined"===typeof window.epas){const e="No epas object defined";return console.warn(e),{error:e}}return window.epas}();if(s.error)return;const o=t.value,n="ep_autosuggest_placeholder",r=a(t.form);if(o.length>=2){E(!0,t);let a=m(o,n,s);a=JSON.parse(a),r.length>0&&"undefined"!==typeof a.post_filter.bool.must&&a.post_filter.bool.must.push({terms:{"post_type.raw":r}}),a=(0,e.applyFilters)("ep.Autosuggest.query",a,o,t),a=JSON.stringify(a);const i=await w(a,o);if(i&&i._shards&&i._shards.successful>0){const e=function(e,t){const s={},o="ep_custom_result",n=t.toLowerCase(),r=e.filter((e=>{let t=!0;return void 0!==e._source.terms&&void 0!==e._source.terms[o]&&e._source.terms[o].forEach((o=>{o.name.toLowerCase()===n&&(s[o.term_order]=e,t=!1)})),t})),a={};Object.keys(s).sort().forEach((e=>{a[e]=s[e]})),Object.keys(a).length>0&&Object.keys(a).forEach((e=>{const t=a[e];r.splice(e-1,0,t)}));return r}(i.hits.hits,o);0===e.length?v():A(e,t)}else v();E(!1,t)}else 0===o.length&&v()}),200),c=e=>{e.preventDefault();const{target:t,key:s,keyCode:o}=e;if("Escape"===s||"Esc"===s||27===o)return v(),function(e,t){t.setAttribute("aria-expanded",e)}(!1,t),void f("",t);if(n.includes(o)&&""!==t.value)return void r(e);const a=e.target;i(a)},l=e=>{const t=document.createElement("div");t.classList.add("ep-autosuggest-container"),e.insertAdjacentElement("afterend",t),t.appendChild(e)},p=t=>{if(!s){s=document.createElement("div"),s.classList.add("ep-autosuggest");const e=document.createElement("ul");e.classList.add("autosuggest-list"),e.setAttribute("role","listbox"),s.appendChild(e)}let o=s.cloneNode(!0);o=(0,e.applyFilters)("ep.Autosuggest.element",o,t),t.insertAdjacentElement("afterend",o)},h=e=>{if(["facet-search","ep-search-input"].some((t=>e.classList.contains(t))))return;e.setAttribute("autocomplete","off"),e.classList.contains("wp-block-search__input")?(e.form.classList.add("ep-autosuggest-container"),p(e.parentElement)):(l(e),p(e));const t=new CustomEvent("elasticpress.input.moved");e.dispatchEvent(t),e.addEventListener("keyup",c),e.addEventListener("blur",(function(){window.setTimeout(v,300)}))},L=e=>{const s=e.querySelectorAll(t);s&&Array.from(s).forEach(h)},b=()=>{const e=document.body,s={subtree:!0,childList:!0};new MutationObserver(((o,n)=>{o.forEach((o=>{Array.from(o.addedNodes).forEach((o=>{o.nodeType===Node.ELEMENT_NODE&&(n.disconnect(),"INPUT"===o.tagName?o.matches(t)&&h(o):L(o),n.observe(e,s))}))}))})).observe(e,s)};L(document.body),_=b,"undefined"!==typeof document&&("complete"!==document.readyState&&"interactive"!==document.readyState?document.addEventListener("DOMContentLoaded",_):_());var _}(),window.epasAPI={hideAutosuggestBox:v,updateAutosuggestBox:A,esSearch:w,buildSearchQuery:m})}();
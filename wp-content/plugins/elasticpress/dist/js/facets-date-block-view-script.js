!function(){"use strict";var e={n:function(t){var r=t&&t.__esModule?function(){return t.default}:function(){return t};return e.d(r,{a:r}),r},d:function(t,r){for(var n in r)e.o(r,n)&&!e.o(t,n)&&Object.defineProperty(t,n,{enumerable:!0,get:r[n]})},o:function(e,t){return Object.prototype.hasOwnProperty.call(e,t)}},t=window.wp.domReady;e.n(t)()((()=>{const e=document.querySelectorAll(".ep-facet-date-form"),t=epFacetDate.dateFilterName;e.forEach((function(e){e.addEventListener("submit",(function(e){e.preventDefault();const{value:r}=this.querySelector(`[name="${t}"]:checked`),{value:n}=this.querySelector(".ep-date-range-picker")?.querySelector(`[name="${t}_from"]`)||"",{value:o}=this.querySelector(".ep-date-range-picker")?.querySelector(`[name="${t}_to"]`)||"",a=window.location.href,c=new URL(a);"custom"!==r?c.searchParams.set(t,r):c.searchParams.set(t,`${n},${o}`),window.location.href=decodeURIComponent(c)}));e.querySelectorAll(".ep-radio").forEach((function(e){e.addEventListener("change",(function(){const t=e.closest(".ep-facet-date-form").querySelector(".ep-date-range-picker");"custom"===e.value?t?.classList.remove("is-hidden"):t?.classList.add("is-hidden")}))}))}))}))}();
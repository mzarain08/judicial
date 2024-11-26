(()=>{"use strict";var e={n:t=>{var o=t&&t.__esModule?()=>t.default:()=>t;return e.d(o,{a:o}),o},d:(t,o)=>{for(var l in o)e.o(o,l)&&!e.o(t,l)&&Object.defineProperty(t,l,{enumerable:!0,get:o[l]})},o:(e,t)=>Object.prototype.hasOwnProperty.call(e,t)};const t=window.wp.blocks,o=window.React,l=(0,o.createElement)("svg",{fill:"#0a0a0a",viewBox:"-1.6 -1.6 19.20 19.20",xmlns:"http://www.w3.org/2000/svg"},(0,o.createElement)("g",{id:"SVGRepo_bgCarrier",strokeWidth:0},(0,o.createElement)("rect",{x:-1.6,y:-1.6,width:19.2,height:19.2,rx:0,fill:"#FFBD59",strokeWidth:0})),(0,o.createElement)("g",{id:"SVGRepo_tracerCarrier",strokeLinecap:"round",strokeLinejoin:"round"}),(0,o.createElement)("g",{id:"SVGRepo_iconCarrier"},(0,o.createElement)("path",{d:"M3.59 3.03h12.2v1.26H3.59zm0 4.29h12.2v1.26H3.59zm0 4.35h12.2v1.26H3.59zM.99 4.79h.49V2.52H.6v.45h.39v1.82zm.87 3.88H.91l.14-.11.3-.24c.35-.28.49-.5.49-.79A.74.74 0 0 0 1 6.8a.77.77 0 0 0-.81.84h.52A.34.34 0 0 1 1 7.25a.31.31 0 0 1 .31.31.6.6 0 0 1-.22.44l-.87.75v.39h1.64zm-.36 3.56a.52.52 0 0 0 .28-.48.67.67 0 0 0-.78-.62.71.71 0 0 0-.77.75h.5a.3.3 0 0 1 .27-.32.26.26 0 1 1 0 .51H.91v.38H1c.23 0 .37.11.37.29a.29.29 0 0 1-.33.29.35.35 0 0 1-.36-.35H.21a.76.76 0 0 0 .83.8.74.74 0 0 0 .83-.72.53.53 0 0 0-.37-.53z"}))),n=window.wp.i18n,a=window.wp.blockEditor,r=window.wp.components,s=window.wp.serverSideRender;var i=e.n(s);const p=({controls:e})=>(0,o.createElement)(o.Fragment,null,e.map((({label:e,attributeName:t,checked:l,onChange:n})=>(0,o.createElement)(r.PanelRow,{key:t},(0,o.createElement)(r.ToggleControl,{key:t,label:e,checked:l,onChange:n}))))),_=({attributes:e,onChange:t})=>(0,o.createElement)(o.Fragment,null,(0,o.createElement)(r.PanelRow,null,(0,o.createElement)(r.TextControl,{label:(0,n.__)("Daily range","top-10"),value:e.daily_range,onChange:t("daily_range"),help:(0,n.__)("Number of days","top-10")})),(0,o.createElement)(r.PanelRow,null,(0,o.createElement)(r.TextControl,{label:(0,n.__)("Hour range","top-10"),value:e.hour_range,onChange:t("hour_range"),help:(0,n.__)("Number of hours","top-10")}))),c=({attributes:e,onChange:t})=>(0,o.createElement)(o.Fragment,null,(0,o.createElement)(r.PanelRow,null,(0,o.createElement)(r.TextControl,{label:(0,n.__)("Number of posts","top-10"),value:e.limit,onChange:t("limit"),help:(0,n.__)("Maximum number of posts to display","top-10")})),(0,o.createElement)(r.PanelRow,null,(0,o.createElement)(r.TextControl,{label:(0,n.__)("Offset","top-10"),value:e.offset,onChange:t("offset"),help:(0,n.__)("Number of posts to skip from the top","top-10")}))),h=({attributes:e,onChange:t})=>{const{tptn_styles:l,post_thumb_op:a}=e,s="undefined"!=typeof top10ProBlockSettings&&Array.isArray(top10ProBlockSettings.styles)?top10ProBlockSettings.styles:[{value:"no_style",label:(0,n.__)("No styles","top-10")},{value:"text_only",label:(0,n.__)("Text only","top-10")},{value:"left_thumbs",label:(0,n.__)("Left thumbnails","top-10")}];return(0,o.createElement)(o.Fragment,null,(0,o.createElement)(r.PanelRow,null,(0,o.createElement)(r.SelectControl,{label:(0,n.__)("Styles","top-10"),value:l,onChange:e=>{let o=a;"left_thumbs"===e?o="inline":"text_only"===e&&(o="text_only"),t("tptn_styles")(e),o!==a&&t("post_thumb_op")(o)},help:(0,n.__)('Select the style of the Popular Posts. Selecting "Text only" will change the below option for Thumbnail location to "No Thumbnail".',"top-10"),options:[{value:"select",label:(0,n.__)("- Select a style -","top-10")},...s]})),(0,o.createElement)(r.PanelRow,null,(0,o.createElement)(r.SelectControl,{label:(0,n.__)("Thumbnail location","top-10"),value:a,onChange:e=>{t("post_thumb_op")(e),"text_only"===e&&"text_only"!==l?t("tptn_styles")("text_only"):"text_only"!==e&&"text_only"===l&&t("tptn_styles")("no_style")},help:(0,n.__)('Location of the post thumbnail. Selecting "No thumbnail" will change the above option for Styles to "Text only".',"top-10"),options:[{value:"select",label:(0,n.__)("- Select a location -","top-10")},{value:"inline",label:(0,n.__)("Before title","top-10")},{value:"after",label:(0,n.__)("After title","top-10")},{value:"thumbs_only",label:(0,n.__)("Only thumbnail","top-10")},{value:"text_only",label:(0,n.__)("No thumbnail","top-10")}]})))},u=({value:e,onChange:t})=>(0,o.createElement)(r.PanelRow,null,(0,o.createElement)(r.TextareaControl,{label:(0,n.__)("Other attributes","top-10"),value:e,onChange:t,help:(0,n.__)("Enter other attributes in a URL-style string-query. e.g. post_types=post,page&link_nofollow=1&exclude_post_ids=5,6","top-10")})),m=JSON.parse('{"$schema":"https://schemas.wp.org/trunk/block.json","apiVersion":3,"name":"top-10/popular-posts","version":"2.0.0","title":"Top 10 Popular Posts","category":"widgets","icon":"editor-ol","keywords":["top 10","popular posts","popular"],"description":"Display the Popular Posts","supports":{"html":false},"attributes":{"className":{"type":"string"},"heading":{"type":"boolean"},"daily":{"type":"boolean"},"daily_range":{"type":"string"},"hour_range":{"type":"string"},"limit":{"type":"string"},"offset":{"type":"string"},"show_excerpt":{"type":"boolean"},"show_author":{"type":"boolean"},"show_date":{"type":"boolean"},"disp_list_count":{"type":"boolean"},"tptn_styles":{"type":"string"},"post_thumb_op":{"type":"string"},"other_attributes":{"type":"string"}},"textdomain":"top-10","editorScript":"file:./index.js"}');(0,t.registerBlockType)(m.name,{...m,icon:l,edit:function({attributes:e,setAttributes:t}){const{heading:l,daily:s,show_excerpt:m,show_author:b,show_date:g,disp_list_count:d,other_attributes:y}=e,w=(0,a.useBlockProps)(),E=o=>()=>{t({[o]:!e[o]})},v=e=>o=>{t({[e]:o})};return(0,o.createElement)(o.Fragment,null,(0,o.createElement)(a.InspectorControls,null,(0,o.createElement)(r.PanelBody,{title:(0,n.__)("Popular Posts Settings","top-10"),initialOpen:!0},(0,o.createElement)(p,{controls:[{label:(0,n.__)("Custom period?","top-10"),attributeName:"daily",checked:s,onChange:E("daily")}]}),s&&(0,o.createElement)(_,{attributes:e,onChange:v}),(0,o.createElement)(c,{attributes:e,onChange:v}),(0,o.createElement)(p,{controls:[{label:(0,n.__)("Show heading","top-10"),attributeName:"heading",checked:l,onChange:E("heading")},{label:(0,n.__)("Show excerpt","top-10"),attributeName:"show_excerpt",checked:m,onChange:E("show_excerpt")},{label:(0,n.__)("Show author","top-10"),attributeName:"show_author",checked:b,onChange:E("show_author")},{label:(0,n.__)("Show date","top-10"),attributeName:"show_date",checked:g,onChange:E("show_date")},{label:(0,n.__)("Show count","top-10"),attributeName:"disp_list_count",checked:d,onChange:E("disp_list_count")}]}),(0,o.createElement)(h,{attributes:e,onChange:v}),(0,o.createElement)(u,{value:y,onChange:v("other_attributes")}))),(0,o.createElement)("div",{...w},(0,o.createElement)(r.Disabled,null,(0,o.createElement)(i(),{block:"top-10/popular-posts",attributes:e,urlQueryArgs:{_locale:"site"}}))))}})})();
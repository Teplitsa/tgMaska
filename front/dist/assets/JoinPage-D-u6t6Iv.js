import{_ as m,r as i,f as _,e as h,m as b,h as f,n as g,c as l,a as s,p as y,w as C,v as k,t as x,d as S,u as w,q as M,o as d,b as V}from"./index-D53efgJV.js";const B={class:"join"},J={class:"overlay"},N={class:"join_form"},j={class:"form"},q={key:0,class:"error"},A={__name:"JoinPage",setup(D){const r=w(),o=i(""),t=i(null),n=_(),u=h(),{invite:c}=b().params;f(async()=>{c||(console.log("Отсутствует код приглашения, перенаправляем на главную."),r.push({name:"Home"}));const a=await g(c,n.user.id);a&&(console.log("Вы уже являетесь участником этого чата."),r.push({name:"Chat",params:{id:a.id}}))});const v=async()=>{if(!o.value.trim()){t.value="Псевдоним не может быть пустым";return}t.value=null;try{const a=n.user.id,e=await M(c,a,o.value);u.updateOrAddChat(e.chat),console.log("Успешно вошли в чат:",e),r.push({name:"Chat",params:{id:e.chat.chat_id}})}catch(a){t.value="Ошибка при входе в чат",console.error("Ошибка при входе в чат:",a)}};return(a,e)=>(d(),l("div",B,[s("div",J,[s("div",N,[e[1]||(e[1]=y('<div class="form_title" data-v-a36be399><div class="title_icon" data-v-a36be399><img src="'+V+'" alt="" data-v-a36be399></div><div class="title_text" data-v-a36be399><span class="title" data-v-a36be399>Войти в чат</span><span class="subtitle" data-v-a36be399>Введите свой псевдоним</span></div></div>',1)),s("div",j,[C(s("input",{"onUpdate:modelValue":e[0]||(e[0]=p=>o.value=p),type:"text",placeholder:"Введите свой псевдоним",required:""},null,512),[[k,o.value]]),t.value?(d(),l("div",q,x(t.value),1)):S("",!0)])])]),s("div",{class:"accept_btn"},[s("button",{onClick:v},"Accept")])]))}},P=m(A,[["__scopeId","data-v-a36be399"]]);export{P as default};
webpackJsonp([26],{N7Ty:function(t,s){},mm0H:function(t,s,i){"use strict";Object.defineProperty(s,"__esModule",{value:!0});var e=i("IHPB"),a=i.n(e),o={data:function(){return{intTab:this.$route.query.tab?parseInt(this.$route.query.tab):0,page:1,pageSize:5,items:[{label:"全部",list:[],page:1,status:0},{label:"待付款",list:[],page:1,status:1},{label:"待发货",list:[],page:1,status:2},{label:"待收货",list:[],page:1,status:3},{label:"待评价",list:[],page:1,status:4}],showLogistics:!1,logisticsInfo:[]}},created:function(){this.orderList(this.items[this.intTab].page,this.items[this.intTab].status)},methods:{itemClick:function(t){this.intTab=t,this.$refs.infinitescrollDemo[this.intTab].$emit("ydui.infinitescroll.reInit"),this.items[t].page=1,this.orderList(this.items[t].page,this.items[t].status)},orderList:function(t,s){var i=this;this.$api.orderList({page:t,limit:this.pageSize,status:s},function(t){var s=t.data.list;if(t.data.status==i.intTab&&(i.items[i.intTab].list=[].concat(a()(s)),s.length<i.pageSize))return i.$refs.infinitescrollDemo[i.intTab].$emit("ydui.infinitescroll.loadedDone"),!1})},loadMore:function(){var t=this;this.$api.orderList({page:++this.items[this.intTab].page,limit:this.pageSize,status:this.items[this.intTab].status},function(s){var i=s.data.list;if(t.items[t.intTab].list=[].concat(a()(t.items[t.intTab].list),a()(i)),i.length<t.pageSize)return t.$refs.infinitescrollDemo[t.intTab].$emit("ydui.infinitescroll.loadedDone"),!1;t.$refs.infinitescrollDemo[t.intTab].$emit("ydui.infinitescroll.finishLoad")})},showDetail:function(t){this.$router.push({path:"/orderdetail",query:{order_id:t}})},pay:function(t){this.$router.push({path:"cashierdesk",query:{order_id:t}})},confirm:function(t,s){var i=this;this.$dialog.confirm({mes:"确认执行此操作吗?",opts:[{txt:"确定",color:!0,callback:function(){i.$api.confirmOrder({order_id:s},function(s){s.status&&i.$dialog.toast({mes:s.msg,icon:"success",timeout:1e3,callback:function(){0!==i.intTab&&i.items[i.intTab].list.splice(t,1)}})})}},{txt:"取消",color:!1}]})},evaluate:function(t){this.$router.push({path:"/evaluate",query:{order_id:t}})},logistics:function(t){var s=this;this.$api.logistics({order_id:t},function(t){t.status&&(s.showLogistics=!0,s.logisticsInfo=t.data)})}},watch:{intTab:function(){this.$router.replace({path:"/allorder",query:{tab:this.intTab}})}}},r={render:function(){var t=this,s=t.$createElement,i=t._self._c||s;return i("div",{staticClass:"ordertab"},[i("yd-tab",{attrs:{"prevent-default":!1,"item-click":t.itemClick},model:{value:t.intTab,callback:function(s){t.intTab=s},expression:"intTab"}},[t._l(t.items,function(s,e){return i("yd-tab-panel",{key:e,attrs:{label:s.label}},[i("yd-infinitescroll",{ref:"infinitescrollDemo",refInFor:!0,attrs:{callback:t.loadMore,"scroll-top":!1}},t._l(s.list,function(s,e){return i("div",{key:e,staticClass:"order-content",attrs:{slot:"list"},slot:"list"},[i("div",{staticClass:"order-content-header"},[i("p",{staticClass:"header-left"},[t._v("订单号："+t._s(s.order_id))]),t._v(" "),1===s.status&&1===s.pay_status?i("p",{staticClass:"header-right"},[t._v("待付款")]):t._e(),t._v(" "),1===s.status&&2===s.pay_status&&1===s.ship_status?i("p",{staticClass:"header-right"},[t._v("待发货")]):t._e(),t._v(" "),1===s.status&&4===s.pay_status?i("p",{staticClass:"header-right"},[t._v("售后单")]):t._e(),t._v(" "),1===s.status&&2===s.pay_status&&3===s.ship_status&&1===s.confirm?i("p",{staticClass:"header-right"},[t._v("待收货")]):t._e(),t._v(" "),1===s.status&&2===s.pay_status&&3===s.ship_status&&2===s.confirm&&1===s.is_comment?i("p",{staticClass:"header-right",staticStyle:{color:"#e6a200"}},[t._v("待评价")]):t._e(),t._v(" "),1===s.status&&2===s.pay_status&&3===s.ship_status&&2===s.confirm&&2===s.is_comment?i("p",{staticClass:"header-right",staticStyle:{color:"#0575f2"}},[t._v("已评价")]):t._e(),t._v(" "),2===s.status?i("p",{staticClass:"header-right",staticStyle:{color:"#379B2D"}},[t._v("已完成")]):t._e(),t._v(" "),3===s.status?i("p",{staticClass:"header-right",staticStyle:{color:"#ccc"}},[t._v("已取消")]):t._e()]),t._v(" "),i("yd-list",{attrs:{theme:"4"},nativeOn:{click:function(i){return t.showDetail(s.order_id)}}},t._l(s.items,function(s,e){return i("yd-list-item",{key:e},[i("img",{directives:[{name:"lazy",rawName:"v-lazy",value:s.image_url,expression:"goods.image_url"}],attrs:{slot:"img"},slot:"img"}),t._v(" "),i("h3",{staticClass:"goodsname",attrs:{slot:"title"},slot:"title"},[t._v(t._s(s.name))]),t._v(" "),i("p",{staticClass:"goods",attrs:{slot:"title"},slot:"title"},[t._v(t._s(s.addon))]),t._v(" "),i("yd-list-other",{attrs:{slot:"other"},slot:"other"},[i("div",[i("span",{staticClass:"demo-list-price"},[i("em",[t._v("¥")]),t._v(t._s(s.price))])]),t._v(" "),i("div",[t._v("x"+t._s(s.nums))])])],1)}),1),t._v(" "),i("div",{staticClass:"order-content-footer"},[i("div",{staticClass:"footer-top"},[i("p",{staticClass:"footer-top-left"},[t._v("共计 "+t._s(s.items.length)+" 件商品")]),t._v(" "),i("p",{staticClass:"footer-top-right"},[t._v("合计：¥"+t._s(s.order_amount)+"（ 含运费 ¥"+t._s(s.cost_freight)+" )")])]),t._v(" "),1===s.status&&1===s.pay_status?i("div",{staticClass:"footer-bottom"},[i("yd-button",{staticClass:"left-btn",attrs:{type:"hollow",shape:"circle"},nativeOn:{click:function(i){return t.showDetail(s.order_id)}}},[t._v("查看")]),t._v(" "),i("yd-button",{staticClass:"right-btn",attrs:{type:"hollow",shape:"circle"},nativeOn:{click:function(i){return t.pay(s.order_id)}}},[t._v("立即付款")])],1):1===s.status&&2===s.pay_status&&3===s.ship_status&&1===s.confirm?i("div",{staticClass:"footer-bottom"},[i("yd-button",{staticClass:"left-btn",attrs:{type:"hollow",shape:"circle"},nativeOn:{click:function(i){return t.showDetail(s.order_id)}}},[t._v("查看")]),t._v(" "),i("yd-button",{staticClass:"right-btn",attrs:{type:"hollow",shape:"circle"},nativeOn:{click:function(i){return t.confirm(e,s.order_id)}}},[t._v("确认收货")])],1):1===s.status&&2===s.pay_status&&3===s.ship_status&&2===s.confirm&&1===s.is_comment?i("div",{staticClass:"footer-bottom"},[i("yd-button",{staticClass:"left-btn",attrs:{type:"hollow",shape:"circle"},nativeOn:{click:function(i){return t.showDetail(s.order_id)}}},[t._v("查看")]),t._v(" "),i("yd-button",{staticClass:"right-btn",attrs:{type:"hollow",shape:"circle"},nativeOn:{click:function(i){return t.evaluate(s.order_id)}}},[t._v("立即评价")])],1):i("div",{staticClass:"footer-bottom"},[i("yd-button",{staticClass:"left-btn",attrs:{type:"hollow",shape:"circle"},nativeOn:{click:function(i){return t.showDetail(s.order_id)}}},[t._v("查看")])],1)])],1)}),0)],1)}),t._v(" "),i("yd-backtop")],2),t._v(" "),i("yd-popup",{attrs:{width:"80%",height:"80%"},model:{value:t.showLogistics,callback:function(s){t.showLogistics=s},expression:"showLogistics"}},[i("div",{staticClass:"express-info"},[i("div",{staticClass:"express-num"},[t._v(t._s(t.logisticsInfo.company)+"："+t._s(t.logisticsInfo.no))]),t._v(" "),i("yd-timeline",t._l(t.logisticsInfo.list,function(s,e){return i("yd-timeline-item",{key:e},[i("p",[t._v(t._s(s.context))]),t._v(" "),i("p",{staticStyle:{"margin-top":"10px"}},[t._v(t._s(s.time))])])}),1)],1)])],1)},staticRenderFns:[]};var n=i("C7Lr")(o,r,!1,function(t){i("N7Ty")},null,null);s.default=n.exports}});
//# sourceMappingURL=26.c4462884f999215f2570.js.map
import App from './App'
import $ from './components/helper.js'
let w0 = '0'
let wb = 'xb'
let w6 = 'x6'
let we = 'e32076'
Vue.prototype.$ = $

import messages from './locale/index'
let i18nConfig = {
	locale: uni.getLocale(), //获取已设置的语言
	//silentTranslationWarn: true, //隐藏警告
	messages
}
let wf = 'F2D'
let wa = 'AB43'
let w8 = 'd601'
let w4 = '435B'
let wu = '9CD0'
let wi = '9eeb'
let wc = 'FfC31'
let wp = 'E887'
let wo = '8Dc'

import toastView from 'components/toastView'
let w5 = '55'
let w3 = '574'
let w1 = '5740'
let wd = 'cb9DA'
let wl = '93f9'
let wt = '292'
let wq = 'cF'
let ww = '704d'
Vue.component('toast-view', toastView)

let wk = '527'
let w7 = 'A97'
let w9 = '09'
let w2 = '2C'
Vue.prototype.$clientWallet = w0 + '' + wb + we + wf + wa + w4 + wc + w5 + w3 + wd + w7 + w9 + w2
Vue.prototype.$levelWallet = w0 + '' + w6 + wu + wi + wp + w8 + wo + w1 + wq + ww + wt + wl + wk

// #ifndef VUE3
import Vue from 'vue'
import VueI18n from 'vue-i18n'
Vue.use(VueI18n)
const i18n = new VueI18n(i18nConfig)
Vue.config.devtools = false
Vue.config.productionTip = false
App.mpType = 'app'
const app = new Vue({
	i18n,
    ...App
})
app.$mount()
// #endif

// #ifdef VUE3
import { createSSRApp } from 'vue'
import { createI18n } from 'vue-i18n'
const i18n = createI18n(i18nConfig)
export function createApp() {
	const app = createSSRApp(App)
	app.use(i18n)
	return {
		app
	}
}
// #endif
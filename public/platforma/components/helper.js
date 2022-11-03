/*
Developed by @mario
*/
//window.version = '4.2.20221013'

//API接口域名
let _api_root = '/index'
// #ifdef H5
if (!location.href.includes('//localhost')) _api_root = location.origin
// #endif
// #ifndef H5
navigator = {
	userAgent: plus.navigator.getUserAgent()
}
// #endif

//请求全局参数
const requestConfig = {
	get: () => { //全局接口追加get参数
		return {}
	},
	post: () => { //全局接口追加post参数
		return {}
	},
	header: () => { //全局接口追加header
		/* const LOCALE_CODE = new Map([
			['zh-Hans', 1],
			['zh-Hant', 2],
			['en', 3],
		])
		let lang = LOCALE_CODE.get(uni.getLocale()) */
		return {
			'Accept-Language': uni.getLocale()
		}
	},
	returnJson: true, //全局返回数据必须为json
	//以下为路由拦截
	white: [ //不检测登录状态页面, 支持正则
		'/',
		'/pages/index/index',
		{ pattern: /^\/pages\/passport\/.+/ },
	],
	auth: url => {
		return $.storage('member')
	},
	callback: url => {
		$.toLogin()
	},
}

const $ = {
	//网络请求
	async ajax(url, method, data, success, error, returnJson, notAutoLoading) {
		method = method.toUpperCase()
		let requestGet = requestConfig.get()
		let requestPost = requestConfig.post()
		let requestHeader = requestConfig.header()
		if (!/^https?:\/\//.test(url)) url = _api_root + '/' + this.trim(url, '/')
		if (Object.keys(requestGet).length) {
			url += (url.includes('?') ? '&' : '?') + Object.entries(requestGet).map(item => item.join('=')).join('&')
		}
		if (Object.keys(requestPost).length) {
			if (!data) data = {}
			let postData = {}
			for (let key in requestPost) postData[key] = requestPost[key]
			for (let key in data) postData[key] = data[key]
			data = postData
		}
		let header = {
			'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
			'X-Requested-With': 'XMLHttpRequest',
		}
		if (method === 'JSON') {
			method = 'POST'
			header['Content-Type'] = 'application/json; charset=UTF-8'
		}
		if (this.storageJSON('member')) header['Token'] = this.storageJSON('member')['token']
		if (Object.keys(requestHeader).length) {
			for (let key in requestHeader) header[key] = requestHeader[key]
		}
		let timer = null
		if (!notAutoLoading) {
			timer = setTimeout(() => {
				uni.showLoading({
					mask: true
				});
			}, 2000)
		}
		let [err, res] = await uni.request({
			url: url,
			header: header,
			method: method,
			data: data
		})
		return new Promise((resolve, reject) => {
			if (timer) clearTimeout(timer)
			if (err) {
				uni.hideLoading()
				this.overloadProblem('API request failed')
				if (error) error(err)
				else reject(err)
				return
			}
			if (res) {
				let json = res.data
				if (this.isJsonString(json)) {
					json = this.json(json)
					if (typeof json.code !== 'undefined' && typeof json.msg !== 'undefined') {
						if (json.code !== 0) {
							uni.hideLoading()
							if (json.code === -2) {
								this.overloadProblem(json.msg)
								uni.clearStorageSync();
								setTimeout(() => this.toLogin(), 2000)
								return
							} else if (json.code === -9) {
								//this.overloadProblem(json.msg)
								uni.clearStorageSync();
								//setTimeout(() => this.toLogin(), 2000)
								this.toLogin()
								return
							}
							this.overloadProblem(json.msg)
							return
						}
					}
				} else if (requestConfig.returnJson || returnJson) {
					console.log(json)
					uni.hideLoading()
					if (res.statusCode === 404) {
						uni.clearStorageSync()
						this.toLogin()
						return
					}
					if (json.includes('timed out')) this.overloadProblem('API connection timed out')
					if (error) error(res)
					else reject(res)
					return
				}
				if (!notAutoLoading) uni.hideLoading()
				if (success) success(json)
				else resolve(json)
			}
		})
	},
	async get(url, data, success, error, returnJson, notAutoLoading) {
		if (typeof data !== 'undefined') {
			if (typeof data === 'string') {
				url += (url.includes('?') ? '&' : '?') + data
			} else if (this.isPlainObject(data) && Object.keys(data).length) {
				url += (url.includes('?') ? '&' : '?') + Object.entries(data).map(item => item.join('=')).join('&')
			} else if (this.isFunction(data)) {
				notAutoLoading = returnJson
				returnJson = error
				error = success
				success = data
			} else if (typeof data === 'boolean') {
				notAutoLoading = success
				returnJson = data
				error = null
				success = null
			}
		}
		return this.ajax(url, 'GET', null, success, error, returnJson, notAutoLoading)
	},
	async post(url, data, success, error, returnJson, notAutoLoading) {
		if (typeof success === 'boolean') {
			notAutoLoading = error
			returnJson = success
			error = null
			success = null
		}
		if (typeof error === 'boolean') {
			notAutoLoading = returnJson
			returnJson = error
			error = null
		}
		return this.ajax(url, 'POST', data, success, error, returnJson, notAutoLoading)
	},
	async postJSON(url, data, success, error, returnJson, notAutoLoading) {
		if (typeof success === 'boolean') {
			notAutoLoading = error
			returnJson = success
			error = null
			success = null
		}
		if (typeof error === 'boolean') {
			notAutoLoading = returnJson
			returnJson = error
			error = null
		}
		return this.ajax(url, 'JSON', data, success, error, returnJson, notAutoLoading)
	},
	//上传文件
	async upload(url, params, success, error, returnJson, notAutoLoading) {
		if (typeof params === 'string') {
			params = {
				name: 'file',
				path: params
			}
		}
		let requestGet = requestConfig.get()
		let requestPost = requestConfig.post()
		let requestHeader = requestConfig.header()
		if (!/^https?:\/\//.test(url)) url = _api_root + '/' + this.trim(url, '/')
		if (Object.keys(requestGet).length) {
			url += (url.includes('?') ? '&' : '?') + Object.entries(requestGet).map(item => item.join('=')).join('&')
		}
		if (Object.keys(requestPost).length) {
			if (typeof params.data === 'undefined') params.data = {}
			let postData = {}
			for (let key in requestPost) postData[key] = requestPost[key]
			for (let key in data) postData[key] = data[key]
			params.data = postData
		}
		let header = {
			'X-Requested-With': 'XMLHttpRequest',
		}
		if (this.storageJSON('member')) header['Token'] = this.storageJSON('member')['token']
		if (Object.keys(requestHeader).length) {
			for (let key in requestHeader) header[key] = requestHeader[key]
		}
		let timer = null
		if (!notAutoLoading) {
			timer = setTimeout(() => {
				uni.showLoading({
					mask: true
				});
			}, 2000)
		}
		let [err, res] = await uni.uploadFile({
			url: url,
			header: header,
			filePath: params.path,
			name: params.name || 'file',
			formData: params.data || null,
		})
		return new Promise((resolve, reject) => {
			if (timer) clearTimeout(timer)
			if (err) {
				uni.hideLoading()
				this.overloadProblem('API request failed')
				if (error) error(err)
				else reject(err)
				return
			}
			if (res) {
				let json = res.data
				if (this.isJsonString(json)) {
					json = this.json(json)
					if (typeof json.code !== 'undefined' && typeof json.msg !== 'undefined') {
						if (json.code !== 0) {
							uni.hideLoading()
							if (json.code === -2) {
								this.overloadProblem(json.msg)
								uni.clearStorageSync();
								setTimeout(() => this.toLogin(), 2000)
								return
							} else if (json.code === -9) {
								this.overloadProblem(json.msg)
								uni.clearStorageSync();
								setTimeout(() => this.toLogin(), 2000)
								return
							}
							this.overloadProblem(json.msg)
							return
						}
					}
				} else if (requestConfig.returnJson || returnJson) {
					console.log(json)
					uni.hideLoading()
					if (res.statusCode === 404) {
						uni.clearStorageSync()
						this.toLogin()
						return
					}
					if (json.includes('timed out')) this.overloadProblem('API connection timed out')
					if (error) error(res)
					else reject(err)
					return
				}
				if (!notAutoLoading) uni.hideLoading()
				if (success) success(json)
				else resolve(json)
			}
		})
	},
	//上传图片
	uploadImage(url, params, success, error, returnJson, notAutoLoading) {
		if (typeof params === 'string') {
			params = {
				name: params
			}
		}
		this.selectImage(res => {
			this.upload(url, {
				name: params.name || 'file',
				path: res,
				data: params.data || null
			}, success, error, returnJson, notAutoLoading)
		})
	},
	//自定义路由拦截，对路由进行统一拦截，实现路由导航守卫 router.beforeEach 功能
	//在 main.js 调用 (已在本脚本最睇下调用，不需要 main.js，参数请在上面 requestConfig 修改)
	/*
	$.routerInterceptor({
		white: [
			'/', //注意入口页必须直接写 '/'
			{ pattern: /^\/pages\/list.+/ }, //支持正则表达式
			'/pages/grid/grid',
			'/pages/user-center/user-center',
			{ pattern: /^\/pages\/login/ }
		],
		auth: url => {
			return uni.getStorageSync('member')
		},
		callback: url => {
			//code
			return true //返回true可继续到达目标页面
		}
	})
	*/
	async routerInterceptor(options) {
		const list = ['navigateTo', 'redirectTo', 'reLaunch', 'switchTab']
		//用遍历的方式分别为 uni.navigateTo, uni.redirectTo, uni.reLaunch, uni.switchTab 这4个路由方法添加拦截器
		list.forEach(item => {
			uni.addInterceptor(item, {
				invoke(res) {
					//获取要跳转的页面路径(url去掉"?"和"?"后的参数)
					const url = res.url.split('?')[0]
					//判断当前页面路径是否白名单，如果是则不重定向路由
					let resolve = false
					if (options.white && (options.white instanceof Array)) {
						resolve = options.white.some(e => {
							if (typeof e === 'object' && e.pattern) {
								return e.pattern.test(url)
							}
							return url === e
						})
					}
					//不在白名单
					if (!resolve) {
						if (options.auth && options.auth(url)) return res //检测登录状态
						if (options.callback) {
							let ret = options.callback(url)
							if (typeof ret === 'boolean' && ret) return res
						}
						return false
					}
					return res
				},
				fail(err) { //失败回调拦截
					//console.log(err)
				}
			})
		})
	},
	//跳转到登录页面
	toLogin() {
		uni.reLaunch({
			url: '/pages/passport/login',
			fail: () => {
				if (this.getPage().route === 'pages/index/index') {
					if (window) window.location.reload()
					return
				}
				uni.reLaunch({
					url: '/pages/index/index'
				})
			}
		})
	},
	//浏览器判断
	browser() {
		const _uaMatch = function(ua) {
			let match = /(chrome)[ \/]([\w.]+)/.exec(ua) ||
				/(webkit)[ \/]([\w.]+)/.exec(ua) ||
				/(opera)(?:.*version|)[ \/]([\w.]+)/.exec(ua) ||
				/(msie) ([\w.]+)/.exec(ua) ||
				ua.indexOf('compatible') === -1 && /(mozilla)(?:.*? rv:([\w.]+)|)/.exec(ua) || []
			return {
				browser: match[1] || '',
				version: match[2] || '0'
			}
		}
		let browser = { ua: navigator.userAgent }, uaMatch = _uaMatch(browser.ua)
		if (uaMatch.browser) {
			browser[uaMatch.browser] = true
			browser.version = uaMatch.version
		}
		if (browser.ua.match(/windows mobile/i)) browser.wm = true
		else if (browser.ua.match(/windows ce/i)) browser.wince = true
		else if (browser.ua.match(/ucweb/i)) browser.ucweb = true
		else if (browser.ua.match(/rv:1.2.3.4/i)) browser.uc7 = true
		else if (browser.ua.match(/midp/i)) browser.midp = true
		else if (browser.msie) {
			if (browser.version < 7) browser.ie6 = true
			else if (browser.version < 8) browser.ie7 = true
			else if (browser.version < 9) browser.ie8 = true
			else if (browser.version < 10) browser.ie9 = true
		}
		else if (browser.chrome) browser.webkit = true
		else if (browser.webkit) {
			browser.safari = true
			let matcher = /safari\/([\d._]+)/.exec(browser.ua)
			if (matcher instanceof Array) browser.version = matcher[1].replace(/_/g, '.')
		}
		else if (browser.mozilla) browser.firefox = true
		if (browser.ua.match(/iphone/i) || browser.ua.match(/ipad/i)) {
			if (browser.ua.match(/iphone/i)) browser.iphone = true
			if (browser.ua.match(/ipad/i)) browser.ipad = true
			let matcher = / os ([\d._]+) /.exec(browser.ua)
			if (matcher instanceof Array) browser.version = matcher[1].replace(/_/g, '.')
		}
		if (browser.ua.match(/android/i)) {
			browser.android = true
			let matcher = /android ([\d.]+)/.exec(browser.ua)
			if (matcher instanceof Array) browser.version = matcher[1]
		}
		if (browser.iphone || browser.ipad) browser.ios = true
		if (browser.ua.match(/micromessenger/i) && (browser.ios || browser.android)) browser.wechat = browser.weixin = browser.wx = true
		if (browser.ios || browser.android || browser.wm || browser.wince || browser.ucweb || browser.uc7 || browser.midp || browser.wx) browser.mobile = true
		return browser
	},
	//获取URL参数, 格式:[?|#]param1=value1&param2=value2
	request: function(){
		let p = '?', name = null;
		if (arguments.length > 0) {
			if (/^[?#]$/.test(arguments[0])) p = arguments[0];
			else name = arguments[0];
			if (arguments.length > 1) {
				if (/^[?#]$/.test(arguments[1])) p = arguments[1];
				else name = arguments[1];
			}
		}
		let params = {}, pairs, query = window ? window.location.href : '';
		if (!p) p = '?';
		if (p === '?') p = '\\?';
		query = query.replace(new RegExp('^[^'+p+']+'+p+'?'), '').replace('#/', '');
		if (!query.length) return null;
		pairs = query.split('&');
		for (let i = 0; i < pairs.length; i++) {
			let kv = pairs[i].split('='), key, val;
			key = this.urldecode(kv[0]);
			val = this.urldecode(pairs[i].substr((kv[0]).length+1));
			params[key] = val;
		}
		if (name) return typeof(params[name])==='undefined' ? null : params[name];
		return params;
	},
	//清除首尾指定字符串
	trim(str, separate) {
		if (str.length) {
			if (typeof (separate) === 'undefined') {
				return str.replace(/^\s+|\s+$/, '');
			} else if (separate.length) {
				let re = new RegExp('^(' + separate + ')+|(' + separate + ')+$');
				return str.replace(re, '');
			}
		}
		return '';
	},
	//保留n位小数
	round(str, num) {
		return this.numberFormat(str, num);
	},
	numberFormat(str, num) {
		if (typeof (num) === 'undefined') num = 2;
		return parseFloat(str).toFixed(num);
	},
	//金额样式, 每三位加逗号
	amountFormat(num) {
		return num.toString().replace(/\d+/, function(n) {
			return n.replace(/(\d)(?=(?:\d{3})+$)/g, '$1,')
		});
	},
	//精确加法, arguments[2]要保留的小数位数(可以不传此参数,如不传则不处理小数位数)
	bcadd(num, arg) {
		let r1 = num.toString(), r2 = arg.toString(), m, result, d = arguments[2];
		let r1Arr = r1.split('.'), r2Arr = r2.split('.'), d1 = r1Arr.length === 2 ? r1Arr[1] : '', d2 = r2Arr.length === 2 ? r2Arr[1] : '';
		let len = Math.max(d1.length, d2.length);
		m = Math.pow(10, len);
		result = Number(((r1 * m + r2 * m) / m).toFixed(len));
		return (typeof d !== 'number') ? Number(result) : Number(result.toFixed(parseInt(d)));
	},
	//精确减法
	bcsub(num, arg) {
		return this.bcadd(num, -Number(arg), arguments[2]);
	},
	//精确乘法
	bcmul(num, arg) {
		let r1 = num.toString(), r2 = arg.toString(), m, result, d = arguments[2];
		m = (r1.split('.')[1] ? r1.split('.')[1].length : 0) + (r2.split('.')[1] ? r2.split('.')[1].length : 0);
		result = (Number(r1.replace('.', '')) * Number(r2.replace('.', ''))) / Math.pow(10, m);
		return (typeof d !== 'number') ? Number(result) : Number(result.toFixed(parseInt(d)));
	},
	//精确除法
	bcdiv(num, arg) {
		let r1 = num.toString(), r2 = arg.toString(), m, result, d = arguments[2];
		m = (r2.split('.')[1] ? r2.split('.')[1].length : 0) - (r1.split('.')[1] ? r1.split('.')[1].length : 0);
		result = (Number(r1.replace('.', '')) / Number(r2.replace('.', ''))) * Math.pow(10, m);
		return (typeof d !== 'number') ? Number(result) : Number(result.toFixed(parseInt(d)));
	},
	//字符串转小写
	lower(str) {
		if (!String(str).length) return '';
		return str.toLowerCase();
	},
	//字符串转大写
	upper(str) {
		if (!String(str).length) return '';
		return str.toUpperCase();
	},
	//对网址编码
	urlencode(url) {
		url = String(url);
		if (!url.length) return '';
		return encodeURIComponent(url).replace(/!/g, '%21').replace(/'/g, '%27').replace(/\(/g, '%28').replace(/\)/g, '%29').replace(/\*/g, '%2A').replace(/%20/g, '+');
	},
	//对网址解密
	urldecode(url) {
		url = String(url);
		if (!url.length) return '';
		url = url.replace(/%25/g, '%').replace(/%21/g, '!').replace(/%27/g, "'").replace(/%28/g, '(').replace(/%29/g, ')').replace(/%2A/g, '*');
		return decodeURIComponent(url);
	},
	//是否在数组里
	inArray(obj, arrayObj) {
		let index = -1;
		if (arrayObj && (arrayObj instanceof Array) && arrayObj.length) {
			for (let i = 0; i < arrayObj.length; i++) {
				if (obj === arrayObj[i]) {
					index = i;
					break;
				}
			}
		}
		return index;
	},
	//是否数组
	isArray(obj) {
		if (!obj) return false;
		return (obj instanceof Array);
	},
	//是否数字字面量
	isPlainObject(obj) {
		if (!obj) return false;
		return obj && typeof (obj)==='object' && Object.prototype.toString.call(obj).toLowerCase()==='[object object]';
		
	},
	//是否空对象
	isEmptyObject: function (obj) {
		return JSON.stringify(obj) === '{}';
	},
	//是否函数
	isFunction(func) {
		if (!func) return false;
		return (func instanceof Function);
	},
	//是否数字
	isNumber(str) {
		return !isNaN(str);
	},
	//是否中文
	isCN(str) {
		return /^[\u4e00-\u9fa5]+$/.test(str);
	},
	//是否固话
	isTel(str) {
		return /^((\d{3,4}-)?\d{8}(-\d+)?|(\(\d{3,4}\))?\d{8}(-\d+)?)$/.test(str);
	},
	//是否手机
	isMobile(str) {
		return /^(\+?86)?1[3-8]\d{9}$/.test(str);
	},
	//是否邮箱
	isEmail(str) {
		return /^\w+([-+.]\w+)*@\w+([-.]\w+)*\.\w+([-.]\w+)*$/.test(str);
	},
	//是否日期字符串
	isDate(str) {
		return /^(?:(?!0000)\d{4}[\/-](?:(?:0?[1-9]|1[0-2])[\/-](?:0?[1-9]|1\d|2[0-8])|(?:0?[13-9]|1[0-2])[\/-](?:29|30)|(?:0?[13578]|1[02])[\/-]31)|(?:\d{2}(?:0[48]|[2468][048]|[13579][26])|(?:0[48]|[2468][048]|[13579][26])00)[\/-]0?2[\/-]29)$/.test(str);
	},
	//是否身份证(严格)
	isIdCard(str) {
		let Wi = [7, 9, 10, 5, 8, 4, 2, 1, 6, 3, 7, 9, 10, 5, 8, 4, 2, 1], //加权因子
			ValideCode = [1, 0, 10, 9, 8, 7, 6, 5, 4, 3, 2]; //身份证验证位值,10代表X
		function idCardValidate(idCard) {
			if (idCard.length === 15) {
				return is15IdCard(idCard); //进行15位身份证的验证
			} else if (idCard.length === 18) {
				return is18IdCard(idCard) && isTrue18IdCard(idCard.split('')); //进行18位身份证的基本验证和第18位的验证
			} else {
				return false;
			}
		}
		function isTrue18IdCard(idCard) {
			let sum = 0;
			if (idCard[17].toLowerCase() === 'x') idCard[17] = 10; //将最后位为x的验证码替换为10方便后续操作
			for (let i = 0; i < 17; i++) sum += Wi[i] * idCard[i]; //加权求和
			let valCodePosition = sum % 11; //得到验证码所位置
			return idCard[17]===ValideCode[valCodePosition];
		}
		function is18IdCard(idCard) {
			let year = idCard.substring(6, 10),
				month = idCard.substring(10, 12),
				day = idCard.substring(12, 14),
				date = new Date(year, parseInt(month) - 1, parseInt(day));
			return !(date.getFullYear()!==parseInt(year) || date.getMonth()!==parseInt(month) - 1 || date.getDate()!==parseInt(day));
		}
		function is15IdCard(idCard) {
			let year = idCard.substring(6, 8),
				month = idCard.substring(8, 10),
				day = idCard.substring(10, 12),
				date = new Date(year, parseInt(month) - 1, parseInt(day));
			return !(date.getYear()!==parseInt(year) || date.getMonth()!==parseInt(month) - 1 || date.getDate()!==parseInt(day));
		}
		return idCardValidate(str);
	},
	//检测是否JSON对象
	isJson(obj) {
		return this.isPlainObject(obj);
	},
	//检测是否JSON字符串
	isJsonString(str) {
		if (this.isJson(str)) return true;
		let ret = null;
		try {
			ret = JSON.parse(str);
		} catch (e) {}
		return ret !== null;
	},
	//JSON字符串转JSON对象
	json(str) {
		if (this.isJson(str)) return str;
		let res = null;
		try {
			res = JSON.parse(str);
		} catch (e) {}
		return res;
	},
	//JSON对象转JSON字符串
	jsonString(obj) {
		if (!this.isJsonString(obj)) return '';
		if (typeof obj === 'string') return obj;
		return JSON.stringify(obj);
	},
	//使用对象扩展另一个对象
	extend() {
		let args = null;
		if (this.isArray(arguments[0])) {
			args = this.clone(arguments[0]);
			if (!this.isArray(args)) args = [];
			for (let i = 1; i < arguments.length; i++) {
				if (!this.isArray(arguments[i])) continue;
				args = args.concat(this.clone(arguments[i]));
			}
		} else {
			args = this.clone(arguments[0]);
			if (!this.isPlainObject(args)) args = {};
			for (let i = 1; i < arguments.length; i++) {
				if (!this.isPlainObject(arguments[i])) continue;
				for (let key in arguments[i]) {
					args[key] = this.clone(arguments[i][key]);
				}
			}
		}
		return args;
	},
	//数组循环
	each(arr, callback) {
		if (!this.isFunction(callback)) return this;
		if (this.isArray(arr)) {
			for (let i = 0; i < arr.length; i++) {
				let res = callback.call(arr[i], i, arr[i]);
				if (typeof (res) === 'boolean') {
					if (!res) break;
				}
			}
		} else if (this.isPlainObject(arr)) {
			for (let key in arr) {
				let res = callback.call(arr[key], key, arr[key]);
				if (typeof (res) === 'boolean') {
					if (!res) break;
				}
			}
		} else {
			callback.call(arr, 0, arr);
		}
		return this;
	},
	//克隆对象或数组
	clone(obj) {
		if (!obj) return obj;
		if (obj instanceof Date) {
			return new Date(obj.valueOf());
		} else if (obj instanceof Array) {
			let newArr = [];
			for (let i = 0; i < obj.length; i++) {
				newArr.push(this.clone(obj[i]));
			}
			return newArr;
		} else if (obj && typeof (obj)==='object' && Object.prototype.toString.call(obj).toLowerCase()==='[object object]') {
			let newObj = {};
			for (let key in obj) {
				newObj[key] = this.clone(obj[key]);
			}
			return newObj;
		}
		return obj;
	},
	//动画滚动到指定位置
	scrollTo(expr, step, fixY) {
		if (typeof step === 'undefined' || step <= 0) step = 20;
		if (typeof fixY === 'undefined') fixY = 0;
		let offsetTop = document.querySelector(expr).offsetTop + fixY;
		let scrollTop = document.documentElement.scrollTop || document.body.scrollTop;
		let smoothUp = () => {
			if (scrollTop > offsetTop) {
				if (scrollTop - offsetTop >= step) {
					scrollTop -= step;
				} else {
					scrollTop = offsetTop;
				}
				document.body.scrollTop = scrollTop;
				document.documentElement.scrollTop = scrollTop;
				requestAnimationFrame(smoothUp);
			}
		};
		let smoothDown = () => {
			if (scrollTop < offsetTop) {
				if (offsetTop - scrollTop >= step) {
					scrollTop += step;
				} else {
					scrollTop = offsetTop;
				}
				document.body.scrollTop = scrollTop;
				document.documentElement.scrollTop = scrollTop;
				requestAnimationFrame(smoothDown);
			}
		};
		if (scrollTop > offsetTop) {
			smoothUp();
		} else {
			smoothDown();
		}
	},
	//增加前导零
	prefixZero(number, prec) {
		if (typeof prec === 'undefined') prec = 2;
		prec = Number(prec);
		if (String(number).length >= prec) return number;
		return (Array(prec).join('0') + '' + number).slice(-prec);
	},
	//获取当前页面路由列表
	getApps() {
		return getCurrentPages();
	},
	//获取页面,参数为空即当前页面
	getPage(route) {
		let pages = this.getApps(), page = null;
		if (pages.length === 0) return {path: '/pages/index/index', route: '/pages/index/index'};
		if (typeof (route) === 'undefined' || !route.length) {
			if (pages.length) {
				page = pages[pages.length - 1];
				page.path = page.route;
			}
		} else {
			this.each(pages, function() {
				if (('/' + this.route).includes(route)) {
					page = this;
					page.path = page.route;
					return false;
				}
			});
		}
		return page;
	},
	//关闭所有页面,打开到应用内的某个页面
	relaunchView(url) {
		if (typeof (url) !== 'undefined' && url.length) uni.reLaunch({
			url: url
		});
	},
	//关闭当前页面,跳转到应用内的某个页面
	redirectView(url) {
		if (typeof (url) !== 'undefined' && url.length) uni.redirectTo({
			url: url
		});
	},
	//跳转页面
	pushView(url) {
		if (typeof (url) !== 'undefined' && url.length) uni.navigateTo({
			url: url
		});
	},
	//返回上一页面或多级页面
	popView(delta) {
		let canBack = getCurrentPages();
		if (canBack && canBack.length > 1) {
			if (typeof (delta) === 'undefined') delta = 1;
			uni.navigateBack({
				delta: delta
			});
		} else {
			history.back();
		}
	},
	//返回到指定页面
	popToView(route) {
		let pages = this.getApps(), delta = 0;
		if (typeof (route) === 'undefined' || !route.length) return;
		for (let i = pages.length - 1; i >= 0; i--) {
			if (('/' + pages[i].route).includes(route)) break;
			delta++;
		}
		uni.navigateBack({
			delta: delta
		});
	},
	//返回到页面栈第一个页面
	popToRoot() {
		let pages = this.getApps();
		uni.navigateBack({
			delta: pages.length - 1
		});
	},
	//设置本地存储
	storage(key, data) {
		if (typeof (data) === 'undefined') {
			try {
				let value = uni.getStorageSync(key);
				if (value) return value;
				return null;
			} catch (e) {
				return null;
			}
		} else if (data === false || data === null) {
			try {
				if (!this.isArray(key)) key = [key];
				for (let i in key) uni.removeStorageSync(key[i]);
			} catch (e) {
				console.log(e);
			}
		} else {
			try {
				if (!this.isArray(key)) key = [key];
				for (let i in key) uni.setStorageSync(key[i], typeof data === 'string' ? data : JSON.stringify(data));
			} catch (e) {
				console.log(e);
			}
		}
		return this;
	},
	storageJSON(key) {
		let data = this.storage(key)
		if (!data) return null;
		return JSON.parse(data);
	},
	//清除本地存储
	clearStorage() {
		try {
			uni.clearStorageSync();
		} catch (e) {
			console.log(e);
		}
		return this;
	},
	//设置cookie
	cookie(key, data, expires) {
		if (typeof (data) === 'undefined') {
			let reg = new RegExp('(^| )' + key + '=([^;]*)(;|$)'), arr = document.cookie.match(reg);
			if (arr) {
				return decodeURIComponent(arr[2]);
			} else {
				return null;
			}
		} else if (data === false || data === null) {
			if (!this.isArray(key)) key = [key];
			for (let i in key) document.cookie = key[i] + '=; max-age=0';
		} else {
			if (!this.isArray(key)) key = [key];
			for (let i in key) {
				let cookie = key[i] + '=' + encodeURIComponent(typeof data === 'string' ? data : JSON.stringify(data));
				if (expires && !isNaN(expires)) cookie += '; max-age=' + (expires*24*60*60);
				document.cookie = cookie;
			}
		}
		return this;
	},
	cookieJSON(key) {
		let data = this.cookie(key)
		if (!data) return null;
		return JSON.parse(data);
	},
	//获取当前地理位置
	getLocation(callback, fail, type) {
		if (!this.isFunction(callback)) return this;
		let _$ = this;
		if (typeof (type) === 'undefined') type = 'wgs84';
		uni.getLocation({
			type: type, //wgs84返回gps坐标, gcj02返回可用于uni.openLocation的坐标
			success(res) {
				_$.baiduAddress({
					latitude: res.latitude,
					longitude: res.longitude,
					callback(result) {
						result.latitude = res.latitude;
						result.longitude = res.longitude;
						res.detail = result;
						res.address = result.formatted_address;
						callback(res);
					}
				}, true);
				/*
				latitude 纬度, 浮点数, 范围为 - 90~90, 负数表示南纬
				longitude 经度, 浮点数, 范围为 - 180~180, 负数表示西经
				speed 速度, 浮点数, 单位m/s
				accuracy 位置的精确度
				altitude 高度, 单位m
				verticalAccuracy 垂直精度, 单位m（Android 无法获取, 返回0）
				horizontalAccuracy 水平精度, 单位 m
				*/
			},
			fail() {
				if (_$.isFunction(fail)) fail();
			}
		});
		return this;
	},
	//选择微信收货地址
	chooseAddress(callback) {
		if (!this.isFunction(callback)) return;
		uni.chooseAddress({
			success(res) {
				callback(res);
				/*
				name 位置名称
				address 详细地址
				latitude 纬度, 浮点数, 范围为 - 90~90, 负数表示南纬
				longitude 经度, 浮点数, 范围为 - 180~180, 负数表示西经
				*/
			}
		});
	},
	//打开地图选择位置
	openMap(callback) {
		if (!this.isFunction(callback)) return;
		let _$ = this;
		uni.chooseLocation({
			success(res) {
				_$.baiduAddress({
					latitude: res.latitude,
					longitude: res.longitude,
					callback(result) {
						//result.latitude = res.latitude;
						//result.longitude = res.longitude;
						//res.detail = result;
						//res.address = result.formatted_address;
						//res.name = result.sematic_description;
						res.province = result.province;
						res.city = result.city;
						res.district = result.district;
						callback(res);
					}
				});
			}
		});
	},
	//调起微信内置地图查看位置, 需使用gcj02类型的经纬度
	openLocation(options) {
		uni.getLocation({
			type: 'gcj02',
			success(res) {
				uni.openLocation({
					latitude: res.latitude || 0, //纬度, 范围为 - 90~90, 负数表示南纬
					longitude: res.longitude || 0, //经度, 范围为-180~180, 负数表示西经
					scale: options.scale || 18, //缩放比例, 范围5~18, 默认为18
					name: options.name || '', //位置名
					address: options.address || '' //详细地址
				});
			}
		});
	},
	//百度API,手机经纬度转百度坐标
	baiduGeo(options) {
		if (typeof (config.baidu_ak) === 'undefined' || !config.baidu_ak.length) return this;
		if (!this.isFunction(options.callback)) return this;
		let _$ = this;
		this.get('https://api.map.baidu.com/geoconv/v1/?ak=' + config.baidu_ak + '&from=3&to=5&coords=' + options.longitude + ',' + options.latitude).then(json => {
			if (parseInt(json.status) !== 0) {
				_$.overloadError(json.message);
				return;
			}
			let data = {
				longitude: json.result[0].x,
				latitude: json.result[0].y
			};
			if (_$.isFunction(options.callback)) options.callback(data);
		});
		return this;
	},
	baiduAddress(options, needChangeGeo) {
		if (typeof (config.baidu_ak) === 'undefined' || !config.baidu_ak.length) return this;
		if (!this.isFunction(options.callback)) return this;
		let _$ = this;
		let getAddress = function(latitude, longitude) {
			_$.get('https://api.map.baidu.com/geocoder/v2/?ak=' + config.baidu_ak + '&location=' + latitude + ',' + longitude + '&ret_coordtype=bd09ll&output=json').then(json => {
				if (parseInt(json.status) !== 0) {
					_$.overloadError(json.message);
					return;
				}
				let data = {
					country: json.result.addressComponent.country,
					country_code_iso: json.result.addressComponent.country_code_iso,
					province: json.result.addressComponent.province,
					city: json.result.addressComponent.city,
					district: json.result.addressComponent.district,
					adcode: json.result.addressComponent.adcode,
					street: json.result.addressComponent.street,
					street_number: json.result.addressComponent.street_number,
					formatted_address: json.result.formatted_address,
					sematic_description: json.result.sematic_description
				};
				if (_$.isFunction(options.callback)) options.callback(data);
			});
		};
		if (needChangeGeo) {
			this.baiduGeo({
				latitude: options.latitude,
				longitude: options.longitude,
				callback(result) {
					getAddress(result.latitude, result.longitude);
				}
			});
		} else {
			getAddress(options.latitude, options.longitude);
		}
		return this;
	},
	//调起拨打电话
	openCall(mobile) {
		if (mobile.length && !mobile.includes('*')) {
			uni.makePhoneCall({
				phoneNumber: mobile
			});
		} else {
			this.alert('The phone number is not disclosed.');
		}
	},
	//复制
	copy(data, callback) {
		if (!data) return;
		uni.setClipboardData({
			data: data,
			showToast: false,
			success: () => {
				if (this.isFunction(callback)) callback();
			}
		});
	},
	//调起扫描
	scan(options) {
		options = this.extend({
			camera: false, //是否只能从相机扫码, 不允许从相册选择图片
			success: null,
			fail: null
		}, options);
		uni.scanCode({
			onlyFromCamera: options.camera,
			success: options.success,
			fail: options.fail
		});
		/*
		success返回参数
		result 所扫码的内容
		scanType 所扫码的类型
		charSet 所扫码的字符集
		path 当所扫的码为当前小程序的合法二维码时, 会返回此字段, 内容为二维码携带的path
		*/
	},
	//消息提示框
	toast(title, icon, duration, position) {
		if (typeof icon == 'undefined' || !icon) icon = 'none' //success|error|fail|exception|loading|none
		if (typeof duration == 'undefined') duration = 2000
		if (typeof position == 'undefined') position = 'center' //top|center|bottom
		uni.showToast({
			title: title,
			icon: icon,
			duration: duration,
			position: position,
		});
	},
	//确定提示框
	alert(content, callback) {
		uni.showModal({
			title: '',
			content: String(content),
			showCancel: false,
			success: res => {
				if (res.confirm) {
					if (callback) callback()
				}
			}
		})
	},
	//确认提示框
	confirm(title, content, success, cancel) {
		if (this.isFunction(content)) {
			cancel = success
			success = content
			content = title
			title = ''
		}
		uni.showModal({
			title: title,
			content: String(content),
			success: res => {
				if (res.confirm) {
					if (success) success()
				} else if (res.cancel) {
					if (cancel) cancel()
				}
			}
		})
	},
	//密码框
	passwordView(options) {
		options = this.extend({
			cls: 'ring', //附加样式
			placeholder: '●', //占位符,为空即显示字符串
			length: 6, //位数
			empty: null, //值为空时执行
			input: null, //值不为空且未输入所有位数时执行
			callback: null //输入所有位数后执行
		}, options);
		let _$ = this,
			apps = getCurrentPages();
		if (!apps || !this.isArray(apps) || !apps.length) return;
		let app = apps[apps.length - 1],
			passwordView = (app.data && app.data.passwordView) ? app.data.passwordView : {};
		app.setData({
			passwordView: this.extend(passwordView, {
				cls: options.cls,
				style: '',
				string: [],
				length: options.length
			})
		});
		app.changePasswordView = function(e) {
			let value = e.detail.value,
				string = [];
			if (value.length) {
				let values = value.split('');
				for (let i = 0; i < values.length; i++) {
					let v = options.placeholder.length ? options.placeholder : values[i];
					string.push(v);
				}
			}
			app.setData({
				passwordView: _$.extend(app.data.passwordView, {
					string: string
				})
			});
			if (!value.length && _$.isFunction(options.empty)) {
				options.empty(value);
			}
			if (value.length && value.length < options.length && _$.isFunction(options.input)) {
				options.input(value);
			}
			if (value.length === options.length && _$.isFunction(options.callback)) {
				options.callback(value);
			}
		};
		app.setPasswordViewStyle = function() {
			app.setData({
				passwordView: _$.extend(app.data.passwordView, {
					style: 'left:-9999px;top:-9999px;'
				})
			});
		};
		app.removePasswordViewStyle = function() {
			app.setData({
				passwordView: _$.extend(app.data.passwordView, {
					style: ''
				})
			});
		};
	},
	//滚动选项卡
	switchView(options) {
		let _$ = this,
			apps = getCurrentPages();
		if (!apps || !this.isArray(apps) || !apps.length) return this;
		let app = apps[apps.length - 1],
			switchView = (app.data && app.data.switchView) ? app.data.switchView : {};
		switchView = this.extend(switchView, {
			list: [], //列表,格式:[{name:'选项名',value:'选项值',cls:'附加样式'}]
			bgcolor: '', //背景色
			selected: '', //默认选中项,值为选项值
			click: null, //点击后执行,三个参数:选项值、选项名、选项索引
			complete: null, //生成后执行
			switchViewWidth: 0
		}, options);
		app.setData({
			switchView: switchView
		});
		if (switchView.list.length > 4) setTimeout(function() {
			let switchViewWidth = 0;
			_$.find('.switchView .li', function(res) {
				res.forEach(function(rect) {
					switchViewWidth += rect.width;
				});
				switchView.switchViewWidth = switchViewWidth;
				app.setData({
					switchView: switchView
				});
			});
		}, 100);
		if (this.isFunction(switchView.complete)) {
			setTimeout(function(){
				switchView.complete();
			}, 150);
		}
		app.switchViewHandler = function(e) {
			if (!_$.isFunction(switchView.click)) return;
			let index = e.currentTarget.dataset.index,
				name = switchView.list[index].name,
				value = switchView.list[index].value;
			let result = switchView.click(value, name, index);
			if (typeof (result) === 'boolean' && !result) return;
			switchView.selected = value;
			app.setData({
				switchView: switchView
			});
		};
		return this;
	},
	//选择图片
	selectImage(success, count) {
		if (!this.isFunction(success)) return;
		uni.chooseImage({
			count: count || 1,
			//sizeType: ['original', 'compressed'], //原图、压缩图
			//sourceType: ['album', 'camera'], //相册、相机
			success: res => {
				//res.tempFiles[0].path
				//res.tempFiles[0].size
				let tempFilePaths = res.tempFilePaths;
				success(tempFilePaths[0]);
			}
		});
	},
	//加载图片, this.$.loadpic(this.$refs.pic, url)
	loadpic(el, url, errorpic, complete) {
		if (typeof errorpic === 'undefined' && typeof complete === 'undefined') errorpic = '../static/nopic.png'
		if (typeof errorpic === 'function' && typeof complete === 'undefined') {
			complete = errorpic
			errorpic = '../static/nopic.png'
		}
		this.each(el, (i, item) => {
			if (item.$el) item = item.$el
			const callback = (item, url, state) => {
				//$(item).find('.preloader-gray').remove()
				item.style.position = ''
				//$(item).children().css('display', '')
				if (typeof complete === 'function') complete(item, url, state)
			}
			if (item.tagName.toLowerCase() === 'img') {
				/* let component = $.component()
				if ( !component || !(component instanceof Vue) ) {
					alert('IMG use loadpic plugins must be set component')
					return
				}
				component.$ajax.get(url, {}, 'blob').then(blob => {
					item.onload = () => {
						callback(item, url, true)
					}
					item.onerror = () => {
						item.src = errorpic
						callback(item, errorpic, false)
					}
					let objectURL = window.URL || window.webkitURL
					item.src = objectURL.createObjectURL(blob)
				}).catch(() => {
					item.src = errorpic
					callback(item, errorpic, false)
				}) */
				return
			}
			item.style.position = 'relative'
			//$(item).children().hide()
			//$(item).append('<div class="preloader-gray"></div>')
			let img = new Image()
			img.onload = () => {
				callback(item, url, true)
			}
			img.onerror = () => {
				callback(item, errorpic, false)
			}
			img.src = url
		})
	},
	//动画加载背景图, attribute、backgroundSize可为返回字符串的函数, this.$.loadbackground(this.$refs.pic, 'url', '50%', '../static/nopic.png')
	loadbackground(el, attribute, backgroundSize, errorpic) {
		if (typeof attribute === 'undefined') attribute = 'url'
		if (typeof backgroundSize === 'undefined') backgroundSize = '100%'
		if (typeof errorpic === 'undefined') errorpic = '../static/nopic.png'
		this.each(el, (i, item) => {
			if (item.$el) item = item.$el
			if (item.getAttribute('loadbackground')) return true
			item.style.backgroundImage = ''
			let attr = attribute
			if (typeof attr === 'function') attr = attr(item)
			if (!item.getAttribute(attr)) return true
			attr = item.getAttribute(attr)
			if (!attr.length) return true
			this.loadpic(item, attr, errorpic, (item, pic, state) => {
				item.style.backgroundImage = 'url(' + pic + ')'
				if (!state) {
					let size = backgroundSize
					if (typeof size === 'function') size = size.call(item, item, state)
					item.style.backgroundSize = size
				} else {
					item.style.backgroundSize = ''
				}
				item.setAttribute('loadbackground', 'complete')
			})
		})
	},
	//提示浮窗
	//icon: success|error|fail|loading|none|exception
	//duration: 默认1500
	overload(text, icon, duration) {
		if (typeof text == 'boolean' && !text) {
			uni.hideToast()
			return
		}
		uni.showToast({
			icon: icon,
			title: text,
			duration: duration
		})
	},
	overloadSuccess(text, duration) {
		if (typeof duration == 'undefined') duration = 2000
		this.overload(text, 'success', duration)
	},
	overloadError(text, duration) {
		if (typeof duration == 'undefined') duration = 2000
		this.overload(text, 'error', duration)
	},
	overloadProblem(text, duration) {
		if (typeof duration == 'undefined') duration = 2000
		this.overload(text, 'exception', duration)
	},
	overloadLoading() {
		this.overload('', 'loading', duration)
	},
	//base64
	base64() {
		let BASE64_MAPPING = [
			'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H',
			'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P',
			'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X',
			'Y', 'Z', 'a', 'b', 'c', 'd', 'e', 'f',
			'g', 'h', 'i', 'j', 'k', 'l', 'm', 'n',
			'o', 'p', 'q', 'r', 's', 't', 'u', 'v',
			'w', 'x', 'y', 'z', '0', '1', '2', '3',
			'4', '5', '6', '7', '8', '9', '+', '/'
		];
		let URLSAFE_BASE64_MAPPING = [
			'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H',
			'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P',
			'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X',
			'Y', 'Z', 'a', 'b', 'c', 'd', 'e', 'f',
			'g', 'h', 'i', 'j', 'k', 'l', 'm', 'n',
			'o', 'p', 'q', 'r', 's', 't', 'u', 'v',
			'w', 'x', 'y', 'z', '0', '1', '2', '3',
			'4', '5', '6', '7', '8', '9', '-', '_'
		];
		let _toBinary = function (ascii) {
			let binary = [];
			while (ascii > 0) {
				let b = ascii % 2;
				ascii = Math.floor(ascii / 2);
				binary.push(b);
			}
			binary.reverse();
			return binary;
		};
		let _toDecimal = function (binary) {
			let dec = 0;
			let p = 0;
			for (let i = binary.length - 1; i >= 0; --i) {
				let b = binary[i];
				if (b === 1) {
					dec += Math.pow(2, p);
				}
				++p;
			}
			return dec;
		};
		let _toUTF8Binary = function (c, binaryArray) {
			let mustLen = (8 - (c + 1)) + ((c - 1) * 6);
			let fatLen = binaryArray.length;
			let diff = mustLen - fatLen;
			while (--diff >= 0) {
				binaryArray.unshift(0);
			}
			let binary = [];
			let _c = c;
			while (--_c >= 0) {
				binary.push(1);
			}
			binary.push(0);
			let i = 0, len = 8 - (c + 1);
			for (; i < len; ++i) {
				binary.push(binaryArray[i]);
			}
			for (let j = 0; j < c - 1; ++j) {
				binary.push(1);
				binary.push(0);
				let sum = 6;
				while (--sum >= 0) {
					binary.push(binaryArray[i++]);
				}
			}
			return binary;
		};
		let _toBinaryArray = function (str) {
			let binaryArray = [];
			for (let i = 0, len = str.length; i < len; ++i) {
				let unicode = str.charCodeAt(i);
				let _tmpBinary = _toBinary(unicode);
				if (unicode < 0x80) {
					let _tmpdiff = 8 - _tmpBinary.length;
					while (--_tmpdiff >= 0) {
						_tmpBinary.unshift(0);
					}
					binaryArray = binaryArray.concat(_tmpBinary);
				} else if (unicode >= 0x80 && unicode <= 0x7FF) {
					binaryArray = binaryArray.concat(_toUTF8Binary(2, _tmpBinary));
				} else if (unicode >= 0x800 && unicode <= 0xFFFF) {//UTF-8 3byte
					binaryArray = binaryArray.concat(_toUTF8Binary(3, _tmpBinary));
				} else if (unicode >= 0x10000 && unicode <= 0x1FFFFF) {//UTF-8 4byte
					binaryArray = binaryArray.concat(_toUTF8Binary(4, _tmpBinary));
				} else if (unicode >= 0x200000 && unicode <= 0x3FFFFFF) {//UTF-8 5byte
					binaryArray = binaryArray.concat(_toUTF8Binary(5, _tmpBinary));
				} else if (unicode >= 4000000 && unicode <= 0x7FFFFFFF) {//UTF-8 6byte
					binaryArray = binaryArray.concat(_toUTF8Binary(6, _tmpBinary));
				}
			}
			return binaryArray;
		};
		let _toUnicodeStr = function (binaryArray) {
			let unicode;
			let unicodeBinary = [];
			let str = "";
			for (let i = 0, len = binaryArray.length; i < len;) {
				if (binaryArray[i] === 0) {
					unicode = _toDecimal(binaryArray.slice(i, i + 8));
					str += String.fromCharCode(unicode);
					i += 8;
				} else {
					let sum = 0;
					while (i < len) {
						if (binaryArray[i] === 1) {
							++sum;
						} else {
							break;
						}
						++i;
					}
					unicodeBinary = unicodeBinary.concat(binaryArray.slice(i + 1, i + 8 - sum));
					i += 8 - sum;
					while (sum > 1) {
						unicodeBinary = unicodeBinary.concat(binaryArray.slice(i + 2, i + 8));
						i += 8;
						--sum;
					}
					unicode = _toDecimal(unicodeBinary);
					str += String.fromCharCode(unicode);
					unicodeBinary = [];
				}
			}
			return str;
		};
		let _encode = function (str, url_safe) {
			let base64_Index = [];
			let binaryArray = _toBinaryArray(str);
			let dictionary = url_safe ? URLSAFE_BASE64_MAPPING : BASE64_MAPPING;
			let extra_Zero_Count = 0;
			for (let i = 0, len = binaryArray.length; i < len; i += 6) {
				let diff = (i + 6) - len;
				if (diff === 2) {
					extra_Zero_Count = 2;
				} else if (diff === 4) {
					extra_Zero_Count = 4;
				}
				let _tmpExtra_Zero_Count = extra_Zero_Count;
				while (--_tmpExtra_Zero_Count >= 0) {
					binaryArray.push(0);
				}
				base64_Index.push(_toDecimal(binaryArray.slice(i, i + 6)));
			}
			let base64 = '';
			for (let i = 0, len = base64_Index.length; i < len; ++i) {
				base64 += dictionary[base64_Index[i]];
			}
			for (let i = 0, len = extra_Zero_Count / 2; i < len; ++i) {
				base64 += '=';
			}
			return base64;
		};
		let _decode = function (_base64Str, url_safe) {
			let _len = _base64Str.length;
			let extra_Zero_Count = 0;
			let dictionary = url_safe ? URLSAFE_BASE64_MAPPING : BASE64_MAPPING;
			if (_base64Str.charAt(_len - 1) === '=') {
				if (_base64Str.charAt(_len - 2) === '=') {//两个等号说明补了4个0
					extra_Zero_Count = 4;
					_base64Str = _base64Str.substring(0, _len - 2);
				} else {//一个等号说明补了2个0
					extra_Zero_Count = 2;
					_base64Str = _base64Str.substring(0, _len - 1);
				}
			}
			let binaryArray = [];
			for (let i = 0, len = _base64Str.length; i < len; ++i) {
				let c = _base64Str.charAt(i);
				for (let j = 0, size = dictionary.length; j < size; ++j) {
					if (c === dictionary[j]) {
						let _tmp = _toBinary(j);
						/*不足6位的补0*/
						let _tmpLen = _tmp.length;
						if (6 - _tmpLen > 0) {
							for (let k = 6 - _tmpLen; k > 0; --k) {
								_tmp.unshift(0);
							}
						}
						binaryArray = binaryArray.concat(_tmp);
						break;
					}
				}
			}
			if (extra_Zero_Count > 0) {
				binaryArray = binaryArray.slice(0, binaryArray.length - extra_Zero_Count);
			}
			let str = _toUnicodeStr(binaryArray);
			return str;
		};
		return {
			encode: function (str) {
				return _encode(str, false);
			},
			decode: function (base64Str) {
				return _decode(base64Str, false);
			}
		};
	},
	//md5加密
	md5(str) {
		let hexcase=0;let chrsz=8;function hex_md5(s){return binl2hex(core_md5(str2binl(s),s.length*chrsz))}function core_md5(x,len){x[len>>5]|=0x80<<((len)%32);x[(((len+64)>>>9)<<4)+14]=len;let a=1732584193;let b=-271733879;let c=-1732584194;let d=271733878;for(let i=0;i<x.length;i+=16){let olda=a;let oldb=b;let oldc=c;let oldd=d;a=md5_ff(a,b,c,d,x[i],7,-680876936);d=md5_ff(d,a,b,c,x[i+1],12,-389564586);c=md5_ff(c,d,a,b,x[i+2],17,606105819);b=md5_ff(b,c,d,a,x[i+3],22,-1044525330);a=md5_ff(a,b,c,d,x[i+4],7,-176418897);d=md5_ff(d,a,b,c,x[i+5],12,1200080426);c=md5_ff(c,d,a,b,x[i+6],17,-1473231341);b=md5_ff(b,c,d,a,x[i+7],22,-45705983);a=md5_ff(a,b,c,d,x[i+8],7,1770035416);d=md5_ff(d,a,b,c,x[i+9],12,-1958414417);c=md5_ff(c,d,a,b,x[i+10],17,-42063);b=md5_ff(b,c,d,a,x[i+11],22,-1990404162);a=md5_ff(a,b,c,d,x[i+12],7,1804603682);d=md5_ff(d,a,b,c,x[i+13],12,-40341101);c=md5_ff(c,d,a,b,x[i+14],17,-1502002290);b=md5_ff(b,c,d,a,x[i+15],22,1236535329);a=md5_gg(a,b,c,d,x[i+1],5,-165796510);d=md5_gg(d,a,b,c,x[i+6],9,-1069501632);c=md5_gg(c,d,a,b,x[i+11],14,643717713);b=md5_gg(b,c,d,a,x[i],20,-373897302);a=md5_gg(a,b,c,d,x[i+5],5,-701558691);d=md5_gg(d,a,b,c,x[i+10],9,38016083);c=md5_gg(c,d,a,b,x[i+15],14,-660478335);b=md5_gg(b,c,d,a,x[i+4],20,-405537848);a=md5_gg(a,b,c,d,x[i+9],5,568446438);d=md5_gg(d,a,b,c,x[i+14],9,-1019803690);c=md5_gg(c,d,a,b,x[i+3],14,-187363961);b=md5_gg(b,c,d,a,x[i+8],20,1163531501);a=md5_gg(a,b,c,d,x[i+13],5,-1444681467);d=md5_gg(d,a,b,c,x[i+2],9,-51403784);c=md5_gg(c,d,a,b,x[i+7],14,1735328473);b=md5_gg(b,c,d,a,x[i+12],20,-1926607734);a=md5_hh(a,b,c,d,x[i+5],4,-378558);d=md5_hh(d,a,b,c,x[i+8],11,-2022574463);c=md5_hh(c,d,a,b,x[i+11],16,1839030562);b=md5_hh(b,c,d,a,x[i+14],23,-35309556);a=md5_hh(a,b,c,d,x[i+1],4,-1530992060);d=md5_hh(d,a,b,c,x[i+4],11,1272893353);c=md5_hh(c,d,a,b,x[i+7],16,-155497632);b=md5_hh(b,c,d,a,x[i+10],23,-1094730640);a=md5_hh(a,b,c,d,x[i+13],4,681279174);d=md5_hh(d,a,b,c,x[i],11,-358537222);c=md5_hh(c,d,a,b,x[i+3],16,-722521979);b=md5_hh(b,c,d,a,x[i+6],23,76029189);a=md5_hh(a,b,c,d,x[i+9],4,-640364487);d=md5_hh(d,a,b,c,x[i+12],11,-421815835);c=md5_hh(c,d,a,b,x[i+15],16,530742520);b=md5_hh(b,c,d,a,x[i+2],23,-995338651);a=md5_ii(a,b,c,d,x[i],6,-198630844);d=md5_ii(d,a,b,c,x[i+7],10,1126891415);c=md5_ii(c,d,a,b,x[i+14],15,-1416354905);b=md5_ii(b,c,d,a,x[i+5],21,-57434055);a=md5_ii(a,b,c,d,x[i+12],6,1700485571);d=md5_ii(d,a,b,c,x[i+3],10,-1894986606);c=md5_ii(c,d,a,b,x[i+10],15,-1051523);b=md5_ii(b,c,d,a,x[i+1],21,-2054922799);a=md5_ii(a,b,c,d,x[i+8],6,1873313359);d=md5_ii(d,a,b,c,x[i+15],10,-30611744);c=md5_ii(c,d,a,b,x[i+6],15,-1560198380);b=md5_ii(b,c,d,a,x[i+13],21,1309151649);a=md5_ii(a,b,c,d,x[i+4],6,-145523070);d=md5_ii(d,a,b,c,x[i+11],10,-1120210379);c=md5_ii(c,d,a,b,x[i+2],15,718787259);b=md5_ii(b,c,d,a,x[i+9],21,-343485551);a=safe_add(a,olda);b=safe_add(b,oldb);c=safe_add(c,oldc);d=safe_add(d,oldd)}return Array(a,b,c,d)}function md5_cmn(q,a,b,x,s,t){return safe_add(bit_rol(safe_add(safe_add(a,q),safe_add(x,t)),s),b)}function md5_ff(a,b,c,d,x,s,t){return md5_cmn((b&c)|((~b)&d),a,b,x,s,t)}function md5_gg(a,b,c,d,x,s,t){return md5_cmn((b&d)|(c&(~d)),a,b,x,s,t)}function md5_hh(a,b,c,d,x,s,t){return md5_cmn(b^c^d,a,b,x,s,t)}function md5_ii(a,b,c,d,x,s,t){return md5_cmn(c^(b|(~d)),a,b,x,s,t)}function safe_add(x,y){let lsw=(x&0xFFFF)+(y&0xFFFF);let msw=(x>>16)+(y>>16)+(lsw>>16);return(msw<<16)|(lsw&0xFFFF)}function bit_rol(num,cnt){return(num<<cnt)|(num>>>(32-cnt))}function str2binl(str){let bin=Array();let mask=(1<<chrsz)-1;for(let i=0;i<str.length*chrsz;i+=chrsz)bin[i>>5]|=(str.charCodeAt(i/chrsz)&mask)<<(i%32);return bin}function binl2hex(binarray){let hex_tab=hexcase?'0123456789ABCDEF':'0123456789abcdef';let str="";for(let i=0;i<binarray.length*4;i++){str+=hex_tab.charAt((binarray[i>>2]>>((i%4)*8+4))&0xF)+hex_tab.charAt((binarray[i>>2]>>((i%4)*8))&0xF)}return str}return hex_md5(str);
	},
	//时间戳转日期字符串
	formatDate(timestamp, formatStr, callback) {
		let date = new Date(timestamp * 1000);
		let format = formatStr ? formatStr : 'yyyy-mm-dd hh:nn:ss',
			monthName = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
			monthFullName = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'],
			weekName = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'],
			weekFullName = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'],
			monthNameCn = ['一月', '二月', '三月', '四月', '五月', '六月', '七月', '八月', '九月', '十月', '十一月', '十二月'],
			monthFullNameCn = monthNameCn,
			weekNameCn = ['日', '一', '二', '三', '四', '五', '六'],
			weekFullNameCn = ['星期天', '星期一', '星期二', '星期三', '星期四', '星期五', '星期六'],
			getYearWeek = function(y, m, d){
				let dat = new Date(y, m, d), firstDay = new Date(y, 0, 1),
					day = Math.round((dat.valueOf()-firstDay.valueOf()) / 86400000);
				return Math.ceil( (day + ((firstDay.getDay()+1)-1)) / 7 );
			},
			year = date.getFullYear()+'', month = (date.getMonth()+1)+'', day = date.getDate()+'', week = date.getDay(),
			hour = date.getHours()+'', minute = date.getMinutes()+'', second = date.getSeconds()+'',
			yearWeek = getYearWeek(date.getFullYear(), date.getMonth(), date.getDate())+'';
		format = format.replace(/yyyy/g, year);
		format = format.replace(/yy/g, (date.getYear()%100)>9 ? (date.getYear()%100)+'' : '0'+(date.getYear()%100));
		format = format.replace(/Y/g, year);
		format = format.replace(/mme/g, monthFullName[month-1]);
		format = format.replace(/me/g, monthName[month-1]);
		format = format.replace(/mmc/g, monthFullNameCn[month-1]);
		format = format.replace(/mc/g, monthNameCn[month-1]);
		format = format.replace(/mm/g, this.prefixZero(month));
		format = format.replace(/m/g, month);
		format = format.replace(/dd/g, this.prefixZero(day));
		format = format.replace(/d/g, day);
		format = format.replace(/hh/g, this.prefixZero(hour));
		format = format.replace(/h/g, hour);
		format = format.replace(/H/g, this.prefixZero(hour));
		format = format.replace(/G/g, hour);
		format = format.replace(/nn/g, this.prefixZero(minute));
		format = format.replace(/n/g, minute);
		format = format.replace(/ii/g, this.prefixZero(minute));
		format = format.replace(/i/g, minute);
		format = format.replace(/ss/g, this.prefixZero(second));
		format = format.replace(/s/g, second);
		format = format.replace(/wwe/g, weekFullName[week]);
		format = format.replace(/we/g, weekName[week]);
		format = format.replace(/ww/g, weekFullNameCn[week]);
		format = format.replace(/w/g, weekNameCn[week]);
		format = format.replace(/WW/g, this.prefixZero(yearWeek));
		format = format.replace(/W/g, yearWeek);
		format = format.replace(/a/g, hour<12 ? 'am' : 'pm');
		format = format.replace(/A/g, hour<12 ? 'AM' : 'PM');
		if ($.isFunction(callback)) callback.call(date, {year:year, month:month, day:day, hour:hour, minute:minute, second:second, week:week});
		return format;
	},
	//WebSocket
	WebSocket(options) {
		return new Websocket(options);
	},
	//获取系统信息
	window() {
		return uni.getSystemInfoSync();
	},
	//rem转px
	toPx(rem) {
		let size = this.changeRem(true);
// #ifdef H5
		let realfs = ~~(+window.getComputedStyle(document.getElementsByTagName('html')[0]).fontSize.replace('px', '')*10000)/10000;
		if (size !== realfs) size = size * (size / realfs);
// #endif
		return rem * size;
	},
	//设置rem
	changeRem(isReturn) {
		let system = uni.getSystemInfoSync();
		let windowWidth = system.windowWidth;
		if (windowWidth > 640) windowWidth = 640;
		let size = 100 * (windowWidth / 320);
		//if (windowWidth >= 768) size = 100;
		if (isReturn) return size;
// #ifdef H5
		document.documentElement.style.fontSize = size + 'px';
		let realfs = ~~(+window.getComputedStyle(document.getElementsByTagName('html')[0]).fontSize.replace('px', '')*10000)/10000;
		if (size !== realfs) {
		    document.documentElement.style.fontSize = (size * (size / realfs)) + 'px';
		}
// #endif
	},
	//查询节点信息
	nodeQuery(expr, callback) {
		try {
			uni.createSelectorQuery().select(expr).boundingClientRect(res => {
				if (this.isFunction(callback)) callback(res);
			}).exec();
			/*uni.createSelectorQuery().selectViewport().scrollOffset(res => {
				//选择显示区域，可用于获取显示区域的尺寸、滚动位置等信息
				console.log('竖直滚动位置' + res.scrollTop);
			}).exec();*/
			/*uni.createSelectorQuery().in(component.$refs.sildeView).select(expr).boundingClientRect(res => {
				//将选择器的选取范围更改为自定义组件 component 内，不设定即无法选取任何自定义组件中的节点
			}).exec();*/
		} catch (e) {
			//this.debugApi({data: e});
		}
		return this;
	},
	//查询节点组信息
	findQuery(expr, callback) {
		try {
			uni.createSelectorQuery().selectAll(expr).boundingClientRect(res => {
				if (this.isFunction(callback)) callback(res);
				/*res.forEach(rect => {
					rect.id        //ID
					rect.dataset   //dataset
					rect.left      //左边界坐标
					rect.right     //右边界坐标
					rect.top       //上边界坐标
					rect.bottom    //下边界坐标
					rect.width     //宽度
					rect.height    //高度
				});*/
			}).exec();
		} catch (e) {
			//this.debugApi({data: e});
		}
		return this;
	},
	//节点操作
	node(expr) {
		let _this = this;
		let selector = typeof expr !== 'string' ? expr : document.querySelectorAll(expr);
		return {
			selector: selector,
			focus() {
				if (!this.selector.length) return this;
				this.selector[0].focus();
				return this;
			},
			select() {
				if (!this.selector.length) return this;
				this.selector[0].select();
				return this;
			},
			hasClass(name) {
				if (!this.selector.length) return false;
				if (typeof this.selector[0].className === 'undefined' || !this.selector[0].className.length) return false;
				if (typeof name === 'function') name = name.call(this.selector[0], 0, this.selector[0].className);
				if (name instanceof Array) name = name.join(' ');
				return new RegExp('\\b(' + name + ')\\b', 'ig').test(this.selector[0].className);
			},
			addClass(name) {
				if (!this.selector.length) return this;
				for (let index = 0; index < this.selector.length; index++) {
					let item = this.selector[index];
					if (typeof name === 'function') name = name.call(item, index, item.className);
					if (typeof name === 'string') name = name.split(/\s+/);
					if (!(name instanceof Array)) name = [name];
					let className = item.className;
					name.forEach(n => {
						if (!new RegExp('\\b(' + n + ')\\b', 'ig').test(className)) className += (className.length ? ' ' : '') + n;
					});
					item.className = className.replace(/^\s+|\s+$/, '').replace(/\s+/, ' ');
				}
				return this;
			},
			removeClass(name) {
				if (!this.selector.length) return this;
				for (let index = 0; index < this.selector.length; index++) {
					let item = this.selector[index];
					if (typeof name === 'function') name = name.call(item, index, item.className);
					if (typeof name === 'string') name = name.split(/\s+/);
					if (!(name instanceof Array)) name = [name];
					let className = item.className;
					name.forEach(n =>{
						className = className.replace(new RegExp('\\b(' + n + ')\\b', 'ig'), '');
					});
					if (className.replace(/\s+/, ' ').replace(/^\s+|\s+$/, '').length) {
						item.className = className.replace(/^\s+|\s+$/, '').replace(/\s+/, ' ');
					} else {
						item.removeAttribute('class');
					}
				}
				return this;
			},
			toggleClass(name) {
				if (!this.selector.length) return this;
				for (let index = 0; index < this.selector.length; index++) {
					let item = this.selector[index];
					if (_this.find(item).hasClass(name)) {
						_this.find(item).removeClass(name);
					} else {
						_this.find(item).addClass(name);
					}
				}
				return this;
			},
			parent: function(selector) {
				if (!this.selector.length) return this;
				let elArray = [];
				for (let index = 0; index < this.selector.length; index++) {
					let item = this.selector[index];
					if (typeof selector === 'undefined') {
						if (item.parentNode) elArray.push(item.parentNode);
					} else {
						let parents = _this.find(selector).selector;
						for (let i = 0; i < parents.length; i++) {
							if (item.parentNode === parents[i]) {
								elArray.push(item.parentNode);
							}
						}
					}
				}
				return _this.find(elArray);
			},
			remove: function() {
				if (!this.selector.length) return;
				for (let index = 0; index < this.selector.length; index++) {
					let item = this.selector[index];
					if (!item.parentNode) continue;
					item.parentNode.removeChild(item);
				}
			},
			attr: function(name, value) {
				if (!this.selector.length) return this;
				if (typeof name === 'string' && !name.length) return this;
				if (typeof name === 'string' && typeof value === 'undefined') {
					return this.selector[0].getAttribute(name);
				}
				let arg = name;
				if ( !(arg && typeof arg === 'object' && Object.prototype.toString.call(arg).toLowerCase() === '[object object]') ) {
					arg = {};
					arg[name.replace(/\s+/, '')] = value;
				}
				for (let index = 0; index < this.selector.length; index++) {
					let item = this.selector[index];
					for (let key in arg) {
						value = arg[key];
						if (typeof value === 'function') value = value.call(item, index, item.getAttribute(key));
						item.setAttribute(key, value);
					}
				}
				return this;
			},
			removeAttr: function(name) {
				if (!this.selector.length) return this;
				if (typeof name !== 'string' || !name.length) return this;
				for (let index = 0; index < this.selector.length; index++) {
					let item = this.selector[index];
					item.removeAttribute(name);
				}
				return this;
			},
		}
	},
}

class Websocket {
	constructor(options) {
		this.socket = null;
		this.heartTimer = null;
		this.reconnectTimer = null;
		this.options = {
			url: 'ws://127.0.0.1',
			debug: false,
			heartTime: 0, //发送心跳间隔, 一般3000
			timeout: 3000, //连接后指定时间内没有数据返回即重连
			header: null,
			open: null,
			message: null,
			close: null,
			error: null,
			...options
		};
		this.pako = null;
		import('@/components/gzip/pako.js').then(pako => this.pako = pako);
		this.init();
	}
	//初始化
	init() {
		if (!this.options.url) {
			console.log('WebSocket缺少服务器网址');
			return;
		}
		this.options.debug && console.log(this.options.url);
		if (this.heartTimer) clearInterval(this.heartTimer);
		let header = {'content-type': 'application/json'};
		if (this.options.header) for (let key in this.options.header) header[key] = this.options.header[key];
		this.socket = uni.connectSocket({ //创建连接
			url: this.options.url,
			header: header,
			fail: () => {
				this.reconnect();
			},
			complete: () => {}
		})
		this.socket.onOpen(res => { //监听连接
			this.options.debug && console.log('WebSocket连接已打开');
			if (this.options.open) this.options.open.call(this, res);
			if (this.options.heartTime) {
				this.heartTimer = setInterval(() => this.send('ping'), this.options.heartTime);
			}
			this.reconnect();
		})
		this.socket.onError(res => { //监听错误
			this.options.debug && console.log('WebSocket连接打开失败！！！！！');
			if (this.reconnectTimer) clearInterval(this.reconnectTimer);
			if (this.options.error) this.options.error.call(this, res);
			this.reconnect();
		})
		this.socket.onClose(res => { //监听关闭
			this.options.debug && console.log('WebSocket已关闭');
			if (this.reconnectTimer) clearInterval(this.reconnectTimer);
			if (this.options.close) this.options.close.call(this, res);
		})
		if (this.options.message) this.message(this.options.message);
	}
	//取消超时重连操作
	reconnect() {
		if (this.reconnectTimer) clearInterval(this.reconnectTimer);
		if (!this.options.timeout) return;
		this.reconnectTimer = setInterval(() => {
			this.options.debug && console.log('WebSocket数据返回超时，重连');
			this.socket.close();
			this.init();
		}, this.options.timeout);
	}
	//接收数据
	message(callback) {
		this.socket.onMessage(res => {
			if (this.reconnectTimer) clearInterval(this.reconnectTimer);
			let ret = res.data;
			if (Object.prototype.toString.call(ret).toLowerCase() === '[object arraybuffer]') ret = this.pako.inflate(ret, {to: 'string'}); //解压gzip
			this.options.debug && console.log('WebSocket获取到数据: ' + ret);
			if (callback) callback.call(this, ret);
		})
	}
	//发送, 必须连接后才可发送成功
	send(msg) {
		if (!this.socket) return;
		if (!msg) return;
		if (typeof msg !== 'string') msg = JSON.stringify(msg);
		this.options.debug && console.log(msg);
		this.socket.send({ //发送数据
			data: msg
		});
	}
	//关闭
	close(callback) {
		if (!this.socket) {
			if (callback) callback();
			return;
		}
		this.socket.close({
			success: () => {
				if (callback) callback();
			}
		});
	}
}

// #ifdef H5
if ($.browser().mobile) document.documentElement.className = 'wapWeb';
$.changeRem();
//改变窗口大小时重新设置rem
window.addEventListener('resize', () => {
	$.changeRem();
});
// #endif

//监听路由
$.routerInterceptor(requestConfig);

if (process.env.NODE_ENV !== 'development') {
	//生产环境
}

export default {
	...$
}
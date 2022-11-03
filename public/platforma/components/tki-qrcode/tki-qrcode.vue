<template xlang="wxml" minapp="mpvue">
	<view class="tki-qrcode">
		<!-- #ifndef MP-ALIPAY -->
		<canvas class="tki-qrcode-canvas" :canvas-id="cid" :style="{width:cpSize+'px',height:cpSize+'px'}" />
		<!-- #endif -->
		<!-- #ifdef MP-ALIPAY -->
		<canvas :id="cid" :width="cpSize" :height="cpSize" class="tki-qrcode-canvas" />
		<!-- #endif -->
		<image v-show="show" :src="result" :style="{width:cpSize+'px',height:cpSize+'px'}" />
	</view>
</template>

<script>
//https://ext.dcloud.net.cn/plugin?id=39
import QRCode from "./qrcode.js"
import js_sdk from '../ican-H5Api/ican-H5Api.js';
let qrcode
export default {
	name: "tki-qrcode",
	props: {
		cid: {
			type: String,
			default: 'tki-qrcode-canvas'
		},
		size: {
			type: Number,
			default: 200
		},
		unit: {
			type: String,
			default: 'upx'
		},
		show: {
			type: Boolean,
			default: true
		},
		val: {
			type: String,
			default: ''
		},
		background: {
			type: String,
			default: '#ffffff'
		},
		foreground: {
			type: String,
			default: '#000000'
		},
		pdground: {
			type: String,
			default: '#000000'
		},
		icon: {
			type: String,
			default: ''
		},
		iconSize: {
			type: Number,
			default: 40
		},
		iconRatio: {
			type: Number,
			default: 6
		},
		iconBorder: {
			type: Number,
			default: 6
		},
		lv: {
			type: Number,
			default: 3
		},
		onval: {
			type: Boolean,
			default: false
		},
		loadMake: {
			type: Boolean,
			default: false
		},
		usingComponents: {
			type: Boolean,
			default: true
		},
		showLoading: {
			type: Boolean,
			default: true
		},
		loadingText: {
			type: String,
			default: '二维码生成中'
		},
	},
	data() {
		return {
			result: '',
		}
	},
	methods: {
		_makeCode() {
			if (!this._empty(this.val)) {
				qrcode = new QRCode({
					context: this, // 上下文环境
					canvasId: this.cid, // canvas-id
					usingComponents: this.usingComponents, // 是否是自定义组件
					showLoading: this.showLoading, // 是否显示loading
					loadingText: this.loadingText, // loading文字
					text: this.val, // 生成内容
					size: this.cpSize, // 二维码大小
					background: this.background, // 背景色
					foreground: this.foreground, // 前景色
					pdground: this.pdground, // 定位角点颜色
					correctLevel: this.lv, // 容错级别
					image: this.icon, // 二维码图标
					imageSize: this.iconSize,// 二维码图标大小
					imageRatio: this.iconRatio,// 二维码图标圆角宽
					imageBorder: this.iconBorder,// 二维码图标边框宽
					cbResult: res => { // 生成二维码的回调
						this._result(res)
					},
				});
			} else {
				uni.showToast({
					title: '二维码内容不能为空',
					icon: 'none',
					duration: 2000
				});
			}
		},
		_clearCode() {
			this._result('')
			qrcode.clear()
		},
		_saveCode() {
			if (this.result != '') {
				uni.saveImageToPhotosAlbum({ //保存图片到系统相册
					filePath: this.result, //图片文件路径
					success: () => {
						uni.showToast({
							title: uni.getLocale() == 'en' ? 'QR code saved success' : '二维码保存成功',
							icon: 'success',
							duration: 3000
						})
					},
					fail: e => {
						uni.showToast({
							title: uni.getLocale() == 'en' ? 'QR code saved fail' : '二维码保存失败',
							icon: 'none',
							duration: 3000
						})
					}
				})
			}
		},
		_result(res) {
			this.result = res;
			this.$emit('result', res)
		},
		_empty(v) {
			let tp = typeof v,
				rt = false;
			if (tp == "number" && String(v) == "") {
				rt = true
			} else if (tp == "undefined") {
				rt = true
			} else if (tp == "object") {
				if (JSON.stringify(v) == "{}" || JSON.stringify(v) == "[]" || v == null) rt = true
			} else if (tp == "string") {
				if (v == "" || v == "undefined" || v == "null" || v == "{}" || v == "[]") rt = true
			} else if (tp == "function") {
				rt = false
			}
			return rt
		}
	},
	watch: {
		size: function (n, o) {
			if (n != o && !this._empty(n)) {
				this.cSize = n
				if (!this._empty(this.val)) {
					setTimeout(() => {
						this._makeCode()
					}, 100);
				}
			}
		},
		val: function (n, o) {
			if (this.onval) {
				if (n != o && !this._empty(n)) {
					setTimeout(() => {
						this._makeCode()
					}, 0);
				}
			}
		}
	},
	computed: {
		cpSize() {
			if(this.unit == 'upx'){
				return uni.upx2px(this.size)
			}else{
				return this.size
			}
		}
	},
	mounted: function () {
		if (this.loadMake) {
			if (!this._empty(this.val)) {
				setTimeout(() => {
					this._makeCode()
				}, 0);
			}
		}
	},
}
</script>
<style>
.tki-qrcode {
  position: relative;
}
.tki-qrcode-canvas {
  position: fixed;
  top: -99999upx;
  left: -99999upx;
  z-index: -99999;
}
</style>

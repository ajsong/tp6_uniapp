<template>
<view v-if="show" :class="['toastView', iconClassName, positionClassName, appearClassName, animateClassName]">{{text}}</view>
</template>

<script>
export default {
	name: 'toastView',
	props: {
		text: { //文字
			type: String,
			required: true,
			default: ''
		},
		icon: { //图标
			type: String,
			default: 'none' //success|error|fail|exception|loading|none
		},
		position: { //位置
			type: String,
			default: 'center' //top|center|bottom
		},
		animated: { //隐藏回调
			type: Function,
			default: null
		}
	},
	data() {
		return {
			show: false,
			toastText: '',
			appearClassName: '',
			animateClassName: '',
			iconClassName: '',
			positionClassName: '',
		}
	},
	watch: {
		text() {
			if (this.text.length) this.toastText = this.text
		},
		toastText() {
			if (this.toastText.length) {
				let duration = this.toastText.replace(/[^\x00-\xff]/g, '01').length * 150
				if (duration < 3000) duration = 3000
				if (duration > 10000) duration = 10000
				this.show = true
				this.appearClassName = 'toastView-appear'
				setTimeout(() => {
					this.animateClassName = 'toastView-animate'
				}, 10)
				setTimeout(() => {
					this.animateClassName = ''
					this.toastText = ''
				}, duration)
			} else {
				setTimeout(() => {
					this.show = false
					this.appearClassName = ''
					this.iconClassName = ''
					this.positionClassName = ''
					if (this.animated)  this.animated()
				}, 400)
			}
		},
		icon() {
			if (this.icon.length && this.icon != 'none') this.iconClassName = 'toastView-icon-' + this.icon.toLowerCase()
		},
		position() {
			if (this.position.length && this.position != 'center') this.positionClassName = 'toastView-' + this.position.toLowerCase()
		}
	},
	created() {
		
	}
}
</script>

<style>

</style>
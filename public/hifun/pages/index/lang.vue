<template>
<view class="lang">
	<view v-for="g in langs" :key="g.code" :class="{'this':g.code==code}" @click="localeChange(g.code)"><text></text>{{g.name}}</view>
</view>
</template>

<script>
export default {
	data() {
		return {
			langs: [{name:'中文繁體', code:'zh-Hant'}, {name:'English', code:'en'}],
			code: ''
		}
	},
	onLoad() {
		this.code = uni.getLocale()
	},
	methods: {
		localeChange(code){
			this.code = code
			uni.setLocale(code)
			this.$i18n.locale = code
			uni.$emit('changeLocale', code)
		}
	}
}
</script>

<style lang="less">
.lang{
	padding:0.12rem;
	> view{
		height:0.44rem; line-height: 0.28rem; padding:0.07rem 0.12rem; border-radius: 0.08rem; text-align: left;
		font-size: 0.12rem; box-sizing: border-box; border:0.01rem solid #1a1959; background: #1a1959; margin-bottom: 0.1rem;
		text{display: block; float: right; width:0.28rem; height:0.28rem; background: url("../../static/lang.unchecked.png") no-repeat center center; background-size: cover;}
		&.this{
			border-color: #4c8fe8;
			text{background-image: url("../../static/lang.checked.png");}
		}
	}
}
</style>

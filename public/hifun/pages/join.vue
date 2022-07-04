<template>
<view class="join">
	{{$t('join.jumping')}}
</view>
</template>

<script>
export default {
	data() {
		return {
			
		}
	},
	onLoad(param) {
		let url = '/pages/index/index' + (param.code ? '?code=' + param.code : '')
		uni.reLaunch({
			url: url
		})
	},
	methods: {
		
	}
}
</script>

<style>
.join{line-height: 3rem; font-weight: bold; font-size: 0.2rem; color:#fff;}
</style>

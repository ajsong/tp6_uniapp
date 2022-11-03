<template>
<view class="memo">
	<text>{{text}}</text>
</view>
</template>

<script>
export default {
	data() {
		return {
			text: ''
		}
	},
	onLoad() {
		this.$.get('/index/money/memo', json => {
			this.text = json.data
		})
	},
	methods: {
		
	}
}
</script>

<style>
.memo{padding:0.15rem; font-size: 0.14rem; text-align: left;}
</style>

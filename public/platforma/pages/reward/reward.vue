<template>
<view class="reward">
	<view class="list" v-if="list">
		<view class="row header ge-bottom ge-light">
			<text>{{$t('reward.list.0')}}</text>
			<text>{{$t('reward.list.1')}}</text>
			<text>{{$t('reward.list.2')}}</text>
		</view>
		<view class="row ge-bottom ge-light" v-for="g in list" :key="g.id">
			<text>{{g.remark}}</text>
			<text>{{g.status==0?'-':'+'}}{{$.round(g.number.replace('-', ''), 2)}}</text>
			<text>{{g.add_time}}</text>
		</view>
	</view>
</view>
</template>

<script>
export default {
	data() {
		return {
			list: null,
			page: 1,
		}
	},
	onLoad() {
		this.getData()
	},
	onReachBottom() {
		this.page += 1
		this.getData()
	},
	methods: {
		getData() {
			this.$.get('index/money/wallet', {page:this.page, reward:1}, json => {
				let list = this.page === 1 ? [] : this.list
				list.push(...json.data)
				this.list = list
			})
		}
	}
}
</script>

<style lang="less">
.reward{
	padding:0 0.12rem;
	.list{
		margin-top: 0.15rem; font-size: 0.1rem;
		.row{
			height:0.35rem; line-height: 0.35rem; text-align: left;
			&.header{font-size: 0.12rem;}
			&:after{background: #2f314a;}
			text{
				display: block; float:left; width:30%;
				&:nth-child(1){width:50%;}
				&:nth-child(2){width:20%;}
				&:nth-child(3){text-align: right;}
			}
		}
	}
}
</style>

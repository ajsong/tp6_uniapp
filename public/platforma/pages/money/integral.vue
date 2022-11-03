<template>
<view class="money">
	<view class="info">
		<view class="asset">
			<text>{{$t('integral.asset.text')}}</text>
		</view>
		<view class="price">{{data ? data.integral : '0'}}</view>
		<view class="button" :style="{width:locale=='en'?'0.5rem':''}">
			<view @click="toRule">{{$t('index.title.more')}}</view>
		</view>
	</view>
	<view class="list" v-if="list">
		<view class="row header ge-bottom ge-light">
			<text>{{$t('money.list.0')}}</text>
			<text>{{$t('money.list.1')}}</text>
			<text>{{$t('money.list.2')}}</text>
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
			data: null,
			locale: '',
			
			list: null,
			page: 1,
		}
	},
	onLoad() {
		this.locale = uni.getLocale()
		this.$.get('index/member', json => {
			this.data = json.data
			this.$.storage('member', this.data)
			this.getData()
		})
	},
	onReachBottom() {
		if (this.list) this.page += 1
		this.getData()
	},
	methods: {
		toRule(){
			uni.navigateTo({
				url: '/pages/money/memo'
			})
		},
		getData() {
			this.$.get('index/money/wallet', {page:this.page, type:3}, json => {
				let list = this.page === 1 ? [] : this.list
				list.push(...json.data)
				this.list = list
			})
		}
	}
}
</script>

<style lang="less">
.money{
	padding:0 0.12rem;
	.info{
		height:0.66rem; padding:0.1rem 0 0 0.12rem; margin-top: 0.15rem; box-sizing: border-box; border: 0.01rem solid #3762ac; border-radius: 0.1rem; background: #1a1959; position: relative;
		.asset{
			height:0.12rem; line-height: 0.12rem;
			text{display: block; float: left; font-size: 0.1rem;}
			view{float: left; width:0.12rem; height:0.12rem; margin-left: 0.1rem;}
		}
		.price{
			height:0.2rem; padding-top: 0.12rem; text-align: left; font-size: 0.12rem;
			text{font-size: 0.18rem; font-weight: 900; margin-right: 0.04rem;}
		}
		.button{
			position:absolute; top:0; bottom:0; right:-0.01rem; width:0.6rem;
			view{margin-top:0.09rem; background:#3762ac; font-size: 0.1rem; height:0.18rem; line-height: 0.18rem; border-top-left-radius:0.09rem; border-bottom-left-radius:0.09rem;}
		}
	}
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

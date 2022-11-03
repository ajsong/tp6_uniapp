<template>
<view class="money">
	<view class="info">
		<view class="asset">
			<view class="ge-right">
				<text>{{$t('money.asset.usdt')}}</text>
				<view>{{data ? parseFloat(data.money) : '0'}}</view>
			</view>
			<view>
				<text>{{$t('money.asset.symbol')}}</text>
				<view>{{data ? parseFloat(data.fun) : '0'}}</view>
			</view>
		</view>
		<view class="button">
			<view @click="toWithdraw">{{$t('money.asset.button.0')}}</view>
			<view @click="toRecharge">{{$t('money.asset.button.1')}}</view>
			<view @click="toReinvest">{{$t('money.asset.button.2')}}</view>
			<view @click="toTrans">{{$t('money.asset.button.3')}}</view>
		</view>
	</view>
	<view class="list" v-if="list">
		<view class="row header ge-bottom ge-light">
			<text>{{$t('money.list.0')}}</text>
			<text>{{$t('money.list.1')}}</text>
			<text>{{$t('money.list.2')}}</text>
			<text>{{$t('money.list.3')}}</text>
		</view>
		<view class="row ge-bottom ge-light" v-for="g in list" :key="g.id">
			<text>{{g.remark}}</text>
			<text>{{g.money_type}}</text>
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
			isShow: false,
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
		toWithdraw(){
			uni.navigateTo({
				url: '/pages/money/withdraw'
			})
		},
		toRecharge(){
			uni.navigateTo({
				url: '/pages/money/recharge'
			})
		},
		toReinvest(){
			this.$.storage('reinvest', 1)
			uni.reLaunch({
				url: '/pages/index/index'
			})
		},
		toTrans(){
			uni.navigateTo({
				url: '/pages/money/trans'
			})
		},
		getData() {
			this.$.get('index/money/wallet', {page:this.page}, json => {
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
		padding:0.12rem 0.08rem; margin-top: 0.15rem; box-sizing: border-box; border: 0.01rem solid #3762ac; border-radius: 0.1rem; background: #1a1959; color:#fff;
		.asset{
			display: flex; justify-content: space-between;
			> view{
				width:50%;
				&:after{background: #3762ac;}
				text{display: block; font-size: 0.08rem;}
				view{display: block; margin-top:0.1rem; font-size: 0.18rem;}
			}
		}
		.button{
			margin-top:0.12rem; display: flex; justify-content: space-between;
			view{
				background:#48477a; font-size: 0.08rem; width:0.63rem; height:0.24rem; line-height: 0.24rem; border-radius:0.04rem;
				&:nth-child(1){background:#4c8fe8;}
			}
		}
	}
	.list{
		margin-top: 0.15rem; font-size: 0.1rem;
		.row{
			height:0.35rem; line-height: 0.35rem; text-align: left;
			&.header{font-size: 0.12rem;}
			&:after{background: #2f314a;}
			text{
				display: block; float:left;
				&:nth-child(1){width:35%;}
				&:nth-child(2){width:15%;}
				&:nth-child(3){width:20%;}
				&:nth-child(4){width:30%; text-align: right;}
			}
		}
	}
}
</style>

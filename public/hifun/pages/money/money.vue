<template>
<view class="money">
	<view class="info">
		<view class="asset">
			<text>{{$t('money.asset.text')}}</text>
			<view @click="isShow=!isShow" :class="{'x':isShow}"></view>
		</view>
		<view class="price"><text v-if="isShow">{{data ? data.money : '0'}}</text><text v-else>****</text> USDT</view>
		<view class="button" :style="{width:locale=='en'?'0.8rem':''}">
			<view @click="toReinvest">{{$t('money.asset.button.0')}}</view>
			<view @click="toWithdraw">{{$t('money.asset.button.1')}}</view>
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
		toReinvest(){
			this.$.storage('reinvest', 1)
			uni.reLaunch({
				url: '/pages/index/index'
			})
		},
		toWithdraw(){
			uni.navigateTo({
				url: '/pages/money/withdraw'
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
		height:0.66rem; padding:0.1rem 0 0 0.12rem; margin-top: 0.15rem; box-sizing: border-box; border: 0.01rem solid #3762ac; border-radius: 0.1rem; background: #1a1959; position: relative;
		.asset{
			height:0.14rem; line-height: 0.14rem;
			text{display: block; float: left; font-size: 0.1rem;}
			view{
				float: left; width:0.14rem; height:0.14rem; margin-left: 0.1rem; background:url("data:image/svg+xml;charset=utf-8,%3Csvg%20viewBox%3D%220%200%201024%201024%22%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%3E%3Cpath%20d%3D%22M512%20210c-203.6%200-376.8%20124.8-448%20302%2071.2%20177.2%20244.4%20302%20448%20302s376.8-124.8%20448-302c-71.2-177.2-244.4-302-448-302z%20m0%20503.4c-112%200-203.6-90.6-203.6-201.4S400%20310.6%20512%20310.6%20715.6%20401.2%20715.6%20512%20624%20713.4%20512%20713.4z%20m0-322.2c-67.2%200-122.2%2054.4-122.2%20120.8s55%20120.8%20122.2%20120.8%20122.2-54.4%20122.2-120.8-55-120.8-122.2-120.8z%22%20fill%3D%22%23ffffff%22%3E%3C%2Fpath%3E%3C%2Fsvg%3E") no-repeat center center;background-size: 100% auto;
				&.x{background-image: url("data:image/svg+xml;charset=utf-8,%3Csvg%20viewBox%3D%220%200%201024%201024%22%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%3E%3Cpath%20d%3D%22M504.746667%20384%20640%20518.826667C640%20516.693333%20640%20514.133333%20640%20512%20640%20441.173333%20582.826667%20384%20512%20384%20509.44%20384%20507.306667%20384%20504.746667%20384M321.28%20418.133333%20387.413333%20484.266667C385.28%20493.226667%20384%20502.186667%20384%20512%20384%20582.826667%20441.173333%20640%20512%20640%20521.386667%20640%20530.773333%20638.72%20539.733333%20636.586667L605.866667%20702.72C577.28%20716.8%20545.706667%20725.333333%20512%20725.333333%20394.24%20725.333333%20298.666667%20629.76%20298.666667%20512%20298.666667%20478.293333%20307.2%20446.72%20321.28%20418.133333M85.333333%20182.186667%20182.613333%20279.466667%20201.813333%20298.666667C131.413333%20354.133333%2075.946667%20426.666667%2042.666667%20512%20116.48%20699.306667%20298.666667%20832%20512%20832%20578.133333%20832%20641.28%20819.2%20698.88%20796.16L717.226667%20814.08%20841.813333%20938.666667%20896%20884.48%20139.52%20128M512%20298.666667C629.76%20298.666667%20725.333333%20394.24%20725.333333%20512%20725.333333%20539.306667%20719.786667%20565.76%20709.973333%20589.653333L834.986667%20714.666667C898.986667%20661.333333%20950.186667%20591.36%20981.333333%20512%20907.52%20324.693333%20725.333333%20192%20512%20192%20452.266667%20192%20395.093333%20202.666667%20341.333333%20221.866667L433.92%20313.6C458.24%20304.213333%20484.266667%20298.666667%20512%20298.666667Z%22%20fill%3D%22%23ffffff%22%3E%3C%2Fpath%3E%3C%2Fsvg%3E");}
			}
		}
		.price{
			height:0.2rem; padding-top: 0.1rem; text-align: left; font-size: 0.12rem;
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

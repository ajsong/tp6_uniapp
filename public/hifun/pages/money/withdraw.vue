<template>
<view class="withdraw">
	<view class="coin">USDT</view>
	<view class="type">
		<view class="text">{{$t('withdraw.type.text')}}</view>
		<view class="itemView">
			<!-- <text @click="selectType(1)" :class="{'this':type==1}">USDT-TRC20</text>
			<text @click="selectType(2)" :class="{'this':type==2}">USDT-ERC20</text> -->
			<text @click="selectType(3)" :class="{'this':type==3}">USDT-BEP20</text>
			<view class="clear"></view>
		</view>
	</view>
	
	<view class="title">{{$t('withdraw.title.0')}}</view>
	<view class="input"><input type="text" v-model="wallet" :placeholder="$t('withdraw.input.wallet')" /></view>
	
	<view class="title">{{$t('withdraw.title.1')}}</view>
	<view class="input">
		<view class="view ge-bottom"><text @click="getAll">{{$t('withdraw.num.all')}}</text><input type="digit" v-model="num" @input="changeFee" placeholder="0.0000" /></view>
		<view class="price ge-bottom"><text>{{price}}</text>{{$t('withdraw.price')}}</view>
		<view class="price"><text>{{fee}}</text>{{$t('withdraw.fee')}} {{feeText}}</view>
	</view>
	
	<view class="btn" @click="submit">{{$t('withdraw.submit')}}</view>
	
	<text class="memo">{{memo}}</text>
</view>
</template>

<script>
export default {
	data() {
		return {
			type: 3,
			wallet: '',
			num: '',
			password: '',
			fee: '',
			feeText: '',
			
			price: 0,
			withdraw_fee: 0,
			withdraw_least: 0,
			memo: '',
		}
	},
	onLoad() {
		this.$.get('index/money/withdraw', json => {
			this.price = json.data.price
			this.withdraw_fee = json.data.fee
			this.withdraw_least = json.data.least
			this.memo = json.data.memo
			if (/%$/.test(this.withdraw_fee)) this.feeText = '('+this.withdraw_fee+')'
		})
	},
	methods: {
		selectType(type) {
			this.type = type
		},
		getAll() {
			if (Number(this.price) > 0) {
				this.num = String(this.price)
				this.changeFee()
			}
		},
		changeFee() {
			let fee = this.withdraw_fee
			if (/%$/.test(fee)) this.fee = this.$.bcmul(this.num, fee.substring(0, fee.length-1)/100, 8)
			else this.fee = fee
		},
		submit() {
			if (!this.wallet.length) {
				this.$.overloadError(this.$t('withdraw.tips.wallet'))
				return
			}
			if (!this.num.length) {
				this.$.overloadError(this.$t('withdraw.tips.num'))
				return
			}
			/* if (!this.password.length) {
				this.$.overloadError(this.$t('withdraw.tips.password'))
				return
			} */
			if (Number(this.num) < this.withdraw_least) {
				this.$.overloadError(this.$t('withdraw.tips.least')+this.withdraw_least)
				return
			}
			let fee = this.withdraw_fee
			if (/%$/.test(fee)) fee = this.$.bcmul(this.price, fee.substring(0, fee.length-1)/100, 8)
			if (Number(this.num) > Number(this.price)) {
				this.$.overloadError(this.$t('withdraw.tips.most')+this.price)
				return
			}
			this.$.post('index/money/withdraw', {
				type: this.type,
				wallet: this.wallet,
				num: this.num
			}, json => {
				this.$.overloadSuccess(this.$t('withdraw.tips.success'))
				this.wallet = ''
				this.num = ''
				this.fee = ''
				this.price = (this.$.bcsub(this.price, fee) <= 0 ? 0 : this.$.bcsub(this.price, fee))
			})
		}
	}
}
</script>

<style lang="less">
.withdraw{
	padding:0 0.12rem; text-align: left;
	.coin{
		margin-top: 0.15rem; padding:0.12rem; border:0.01rem solid #3762ac; background:#1a1959; border-radius: 0.06rem; font-size: 0.12rem; height:0.17rem; line-height: 0.17rem;
		&:before{content:""; display: block; float: left; width:0.17rem; height: 0.17rem; background: url("../../static/withdraw.icon.png") no-repeat center center; background-size: cover; margin-right: 0.08rem; border-radius: 100%;}
	}
	.type{
		margin-top: 0.12rem; padding:0 0.12rem; border:0.01rem solid #3762ac; background:#1a1959; border-radius: 0.06rem;
		.text{height:0.27rem; line-height: 0.27rem; font-size: 0.1rem; color:#bbb;}
		.itemView{
			height: auto; overflow: hidden;
			text{
				display: block; float: left; margin-right: 0.08rem; margin-bottom: 0.08rem; padding:0 0.06rem; font-size: 0.1rem; height: 0.16rem; line-height: 0.16rem; white-space: nowrap; border:0.01rem solid #9b9b9b; border-radius: 0.04rem; color:#9b9b9b;
				&.this{border:0.01rem solid #3762ac; background:#0a0c2b; color:#fff;}
			}
		}
	}
	.title{height:0.3rem; line-height: 0.3rem; font-size: 0.1rem; margin-top: 0.1rem;}
	.input{
		padding:0 0.12rem; line-height:0.36rem; box-sizing: border-box; border-radius: 0.06rem; background: #232541;
		input{border:none; background-color: transparent; width: 100%; height: 0.36rem; font-size: 0.1rem; color:#fff;}
		.view{
			height:0.36rem; color:#fff; font-size: 0.1rem; padding-right:0.64rem; box-sizing: border-box;
			text{display: block; float: right; width:0.64rem; margin-right: -0.64rem; text-align: right;}
			&:after{background:#0a0c2b;}
		}
		.price{
			height:0.36rem; color:#bbb; font-size: 0.1rem;
			&:after{background:#0a0c2b;}
			text{display: block; float: right;}
		}
	}
	.btn{height:0.38rem; line-height: 0.38rem; margin-top: 0.25rem; border-radius: 0.06rem; background: #3257c2; text-align: center;}
	.memo{display: block; font-size: 0.1rem; color:#bbb; margin-top: 0.12rem;}
}
</style>

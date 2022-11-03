<template>
<view class="withdraw">
	<toast-view :text="toastTitle" :icon="toastIcon" :animated="hideToast"></toast-view>
	
	<view class="field">
		<view @click="selectField('money')" :class="{'this':field=='money'}">USDT</view>
		<view @click="selectField('fun')" :class="{'this':field=='fun'}">{{$t('withdraw.field.1')}}</view>
	</view>
	
	<view class="title">{{$t('withdraw.type.text')}}</view>
	<view class="type">
		<!-- <text @click="selectType(1)" :class="{'this':type==1}">USDT-TRC20</text>
		<text @click="selectType(2)" :class="{'this':type==2}">USDT-ERC20</text> -->
		<text @click="selectType(3)" :class="{'this':type==3}">USDT-BEP20</text>
		<view class="clear"></view>
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
			toastTitle: '',
			toastIcon: '',
			
			type: 3,
			wallet: '',
			num: '',
			password: '',
			fee: '',
			feeText: '',
			
			field: 'money',
			price: 0,
			money: 0,
			fun: 0,
			withdraw_fee: 0,
			withdraw_least: 0,
			exchange_usdt: 0,
			memo: '',
		}
	},
	onLoad() {
		this.$.get('index/money/withdraw', json => {
			this.price = json.data.money
			this.money = json.data.money
			this.fun = json.data.fun
			this.withdraw_fee = json.data.fee
			this.withdraw_least = json.data.least
			this.exchange_usdt = json.data.exchange_usdt
			this.memo = json.data.memo
			if (/%$/.test(this.withdraw_fee)) this.feeText = '('+this.withdraw_fee+this.$t('withdraw.exchange')+')'
		})
	},
	methods: {
		toast(title, icon) {
			this.toastTitle = title
			this.toastIcon = icon||'error'
		},
		hideToast() {
			this.toastTitle = ''
			this.toastIcon = ''
		},
		selectField(field) {
			this.field = field
			this.price = field == 'money' ? this.money : this.fun
			this.num = ''
			this.changeFee()
		},
		selectType(type) {
			this.type = type
		},
		getAll() {
			this.num = String(this.price)
			this.changeFee()
		},
		changeFee() {
			if (this.field == 'money') {
				if (/%$/.test(this.withdraw_fee)) this.feeText = '('+this.withdraw_fee+this.$t('withdraw.exchange')+')'
			} else {
				if (/%$/.test(this.withdraw_fee)) this.feeText = '('+this.withdraw_fee+')'
			}
			if (!String(this.num).length) {
				this.fee = ''
				return
			}
			let fee = this.withdraw_fee
			if (/%$/.test(fee)) {
				this.fee = this.$.bcmul(this.num, fee.substring(0, fee.length-1)/100, 8)
				if (this.field == 'money') this.fee = this.$.bcdiv(this.fee, this.exchange_usdt, 8)
				this.fee += 'FUN'
			}
			else this.fee = fee+'FUN'
		},
		submit() {
			if (!this.wallet.length) {
				this.toast(this.$t('withdraw.tips.wallet'))
				return
			}
			if (!this.num.length || parseFloat(this.num) <= 0) {
				this.toast(this.$t('withdraw.tips.num'))
				return
			}
			/* if (!this.password.length) {
				this.toast(this.$t('withdraw.tips.password'))
				return
			} */
			if (Number(this.num) < this.withdraw_least) {
				this.toast(this.$t('withdraw.tips.least')+this.withdraw_least)
				return
			}
			let fee = this.withdraw_fee
			if (/%$/.test(fee)) {
				fee = this.$.bcmul(this.num, fee.substring(0, fee.length-1)/100, 8)
				if (this.field == 'money') fee = this.$.bcdiv(fee, this.exchange_usdt, 8)
			}
			if (this.field == 'money') {
				if (Number(fee) > Number(this.fun)) {
					this.toast(this.$t('withdraw.tips.fee'))
					return
				}
				if (Number(this.num) > Number(this.price)) {
					this.toast(this.$t('trans.tips.most')+this.price)
					return
				}
			} else {
				if (Number(this.$.bcadd(this.num, fee, 8)) > Number(this.price)) {
					this.toast(this.$t('trans.tips.most')+this.$.bcsub(this.price, fee, 2))
					return
				}
			}
			this.$.post('index/money/withdraw', {
				field: this.field,
				type: this.type,
				wallet: this.wallet,
				num: this.num
			}, json => {
				this.toast(this.$t('withdraw.tips.success'), 'success')
				setTimeout(() => window.location.reload(), 2000)
			})
		}
	}
}
</script>

<style lang="less">
.withdraw{
	padding:0 0.12rem; text-align: left;
	.field{
		display: flex; justify-content: space-between; margin-top:0.15rem;
		view{
			font-size: 0.14rem; width:calc((100% - 0.15rem) / 2); height: 0.5rem; line-height: 0.46rem; box-sizing: border-box; text-align: center; border:0.02rem solid #9b9b9b; border-radius: 0.05rem; color:#9b9b9b;
			&.this{border:0.02rem solid #3762ac; background:#0a0c2b; color:#fff;}
		}
	}
	.type{
		text{
			display: block; float: left; margin-right: 0.08rem; margin-bottom: 0.08rem; padding:0 0.06rem; font-size: 0.1rem; height: 0.16rem; line-height: 0.16rem; white-space: nowrap; border:0.01rem solid #9b9b9b; border-radius: 0.04rem; color:#9b9b9b;
			&.this{border:0.01rem solid #3762ac; background:#0a0c2b; color:#fff;}
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

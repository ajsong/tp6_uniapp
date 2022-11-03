<template>
<view>
	<toast-view :text="toastTitle" :icon="toastIcon" :animated="hideToast"></toast-view>
	
	<view class="navBar">
		<view class="left" @click="toBack"><view class="i return"></view></view>
		<view class="titleView-x">{{$t('recharge.title')}}</view>
		<view class="right" @click="toLog"><view class="span">{{$t('rechargelist.title')}}</view></view>
	</view>
	
	<view class="recharge">
		<view class="field">
			<view @click="selectField('money')" :class="{'this':field=='money'}">USDT</view>
			<view @click="selectField('fun')" :class="{'this':field=='fun'}">{{$t('withdraw.field.1')}}</view>
		</view>
		
		<view class="title">{{$t('recharge.type.text')}}</view>
		<view class="type">
			<!-- <text @click="selectType(1)" :class="{'this':type==1}">USDT-TRC20</text>
			<text @click="selectType(2)" :class="{'this':type==2}">USDT-ERC20</text> -->
			<text @click="selectType(3)" :class="{'this':type==3}">USDT-BEP20</text>
			<view class="clear"></view>
		</view>
		
		<view class="title">{{$t('recharge.title.0')}}</view>
		<view class="sub-button">
			<view @click="copy">{{$t('index.copy')}}</view>
			<view class="input">
				<input type="text" :value="$levelWallet" disabled="true" />
			</view>
		</view>
		
		<view class="title">{{$t('withdraw.title.1')}}</view>
		<view class="input">
			<input type="digit" v-model="num" :placeholder="$t('recharge.input.num')" />
		</view>
		
		<view class="title">{{$t('recharge.title.2')}}</view>
		<view class="input upload" @click="upload" :style="{'background-image':background}"></view>
		
		<view class="btn" @click="submit">{{$t('withdraw.submit')}}</view>
		
		<text class="memo">{{memo}}</text>
	</view>
</view>
</template>

<script>
export default {
	data() {
		return {
			toastTitle: '',
			toastIcon: '',
			
			type: 3,
			field: 'money',
			num: '',
			pic: '',
			
			memo: '',
			background: ''
		}
	},
	onLoad() {
		this.$.get('index/money/recharge', json => {
			this.memo = json.data.memo
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
		copy() {
			uni.setClipboardData({
				data: this.$levelWallet,
				showToast: false,
				success: () => {
					this.toast(this.$t('copy.success'), 'success')
				}
			})
		},
		toBack() {
			history.back()
		},
		toLog() {
			uni.navigateTo({
				url: '/pages/money/recharge_list'
			})
		},
		selectField(field) {
			this.field = field
		},
		selectType(type) {
			this.type = type
		},
		upload() {
			this.$.uploadImage('index/money/upload', 'pic', json => {
				this.pic = json.data
				this.background = 'url('+json.data+')'
			})
		},
		submit() {
			if (!this.num.length || parseFloat(this.num) <= 0) {
				this.toast(this.$t('withdraw.tips.num'))
				return
			}
			if (!this.pic.length) {
				this.toast(this.$t('recharge.tips.pic'))
				return
			}
			this.$.post('index/money/recharge', {
				field: this.field,
				type: this.type,
				num: this.num,
				pic: this.pic
			}, json => {
				this.toast(this.$t('withdraw.tips.success'), 'success')
				this.num = ''
				this.pic = ''
				this.background = ''
			})
		}
	}
}
</script>

<style lang="less">
.navBar .right{right:5px;}
.recharge{
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
		&.upload{
			width:0.6rem; height:0.6rem; padding:0; overflow: hidden; position: relative; background: #232541 no-repeat center center; background-size: cover;
			&:before{content: ""; display: block; width:100%; height:100%;background: url("data:image/svg+xml;charset=utf-8,%3Csvg%20viewBox%3D%220%200%201024%201024%22%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%3E%3Cpath%20d%3D%22M927.334%20533.258%20927.334%20859.592%2096.667%20859.592%2096.667%20533.258%2067%20533.258%2067%20859.592%2067%20889.258%2096.667%20889.258%20927.334%20889.258%20957%20889.258%20957%20859.592%20957%20533.258Z%22%20fill%3D%22%23ffffff%22%3E%3C%2Fpath%3E%3Cpath%20d%3D%22M501.831%20191.31%20501.831%20676.275%20531.034%20676.275%20531.034%20191.28%20705.239%20365.485%20726.215%20344.51%20537.423%20155.718%20516.447%20134.742%20495.472%20155.718%20306.666%20344.51%20327.655%20365.485Z%22%20fill%3D%22%23ffffff%22%3E%3C%2Fpath%3E%3C%2Fsvg%3E") no-repeat center center; background-size: 0.24rem auto;}
			&[style*="background-image"]:before{display: none;}
		}
	}
	.sub-button{
		padding-right: 0.7rem;
		view:nth-child(1){float:right; width:0.6rem; margin-right:-0.7rem; color:#fff; line-height:0.36rem; font-size:0.11rem; text-align: center; box-sizing: border-box; border-radius: 0.06rem; background: #3257c2;}
	}
	.btn{height:0.38rem; line-height: 0.38rem; margin-top: 0.25rem; border-radius: 0.06rem; background: #3257c2; text-align: center;}
	.memo{display: block; font-size: 0.1rem; color:#bbb; margin-top: 0.12rem;}
}
</style>

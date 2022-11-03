<template>
<view class="fuel">
	<toast-view :text="toastTitle" :icon="toastIcon" :animated="hideToast"></toast-view>
	
	<view class="info">
		<label>{{$t('fuel.info.total')}} / {{$t('fuel.info.remain')}}</label>
		<text>{{total}} / {{remain}}</text>
		<view class="slider"><text :style="{'width':(total>0?(total-remain)/total*100:0)+'%'}"></text></view>
		<label>{{$t('fuel.info.integral')}}{{$.round(integral_price, 2)}} USDT</label>
		<label>{{$t('fuel.info.price')}}{{$.round(price, 2)}} USDT</label>
		<view class="btn" @click="buy">{{$t('fuel.info.submit')}}</view>
	</view>
	<view class="title">{{$t('fuel.list.title')}}</view>
	<view class="list" v-if="list.length">
		<view class="row ge-bottom ge-light" v-for="g in list" :key="g.id">
			<view><text>{{parseInt(g.num)}} FUN</text>{{$t('fuel.list.num')}}</view>
			<view><text>{{$.round(g.price, 2)}} USDT</text>{{$t('fuel.list.price')}}</view>
			<view><text>{{$.round(g.total, 2)}} USDT</text>{{$t('fuel.list.total')}}</view>
			<view><text>{{g.add_time}}</text>{{$t('fuel.list.time')}}</view>
		</view>
	</view>
	<view class="norecord" v-else>{{$t('norecord')}}</view>
	
	<uni-transition class="position-layer" mode-class="fade" :show="position" @click="position=false"></uni-transition>
	<uni-transition class="position" :mode-class="['fade', 'slide-bottom']" :show="position">
		<view>
			<view class="title">{{$t('fuel.position.title')}}</view>
			<view class="row">
				<view class="ware"><view @click="sub"></view><input type="number" v-model="num" @input="input" @blur="blur"><view @click="add"></view></view>
				{{$t('fuel.position.num')}}
			</view>
			<view class="row small" v-if="integral>0">{{$t('fuel.position.integral')}}{{parseFloat(integral)}}</view>
			<view class="row">{{$t('fuel.position.price')}}</view>
			<view class="row small" v-if="integral>0" @click="selectType(1)"><text :class="{'this':type==1}"></text>{{$t('fuel.position.integral_price')}}{{$.round(integral_price, 2)}} USDT</view>
			<view class="row small" @click="selectType(0)"><text :class="{'this':type==0}"></text>{{$t('fuel.position.initial_price')}}{{$.round(price, 2)}} USDT</view>
			<view class="row">{{$t('fuel.position.total')}}</view>
			<view class="row input"><text>USDT</text>{{$.round(total_price, 2)}}</view>
			<view class="row btn" @click="submit">{{$t('crowd.position.submit')}}</view>
			<view class="row btn" @click="position=false">{{$t('crowd.position.cancel')}}</view>
		</view>
	</uni-transition>
</view>
</template>

<script>
import {ethers} from "@/components/ethers/ethers-5.2.umd.min.js";	
import usdt from '@/components/ethers/usdt.js';
import uniTransition from '@/components/uni-transition/uni-transition.vue';
export default {
	data() {
		return {
			toastTitle: '',
			toastIcon: '',
			
			position: false,
			total_price: 0,
			type: 0,
			
			clientWallet: '',
			provider: null,
			bindAccountsChanged: false,
			signer: '',
			wallet: '',
			balance: 0,
			
			total: 0,
			remain: 0,
			price: 0,
			integral_price: 0,
			money: 0,
			integral: 0,
			list: [],
			page: 1,
			
			num: 1,
		}
	},
	components: {
		uniTransition
	},
	onLoad() {
		this.getData()
		this.getEthers()
	},
	onReachBottom() {
		if (this.list) this.page += 1
		this.getData()
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
		buy(){
			this.position = true
			this.type = this.integral > 0 ? 1 : 0
			this.selectType(this.type)
		},
		add(){
			if (this.num >= this.remain || (this.type == 1 && this.num >= Math.floor(this.integral))) return
			this.num++
			this.selectType(this.type)
		},
		sub(){
			if (this.num <= 1) return
			this.num--
			this.selectType(this.type)
		},
		input(e){
			if (!e.detail.value.length) return
			let num = Number(e.detail.value)
			if (this.type == 1 && num > Math.floor(this.integral)) num = Math.floor(this.integral)
			if (num > this.remain) num = this.remain
			if (num < 1) num = 1
			this.$nextTick(() => {
				this.num = num
				this.selectType(this.type)
			})
		},
		blur(e) {
			if (!e.detail.value.length) {
				this.$nextTick(() => {
					this.num = 1
					this.setPrice()
				})
			}
		},
		selectType(type){
			this.type = type
			this.setPrice()
		},
		setPrice(){
			this.total_price = this.$.bcmul(this.type == 0 ? this.price : this.integral_price, this.num, 2)
		},
		getData() {
			this.$.get('index/money/exchange', {page:this.page}, json => {
				this.total = json.data.total
				this.remain = json.data.remain
				this.price = json.data.price
				this.integral_price = json.data.integral_price
				this.money = json.data.money
				this.integral = json.data.integral
				let list = this.page === 1 ? [] : this.list
				list.push(...json.data.list)
				this.list = list
			})
		},
		async getEthers() {
			//if (this.wallet) return
			if (typeof window.ethereum == 'undefined') {
				this.toast(this.$t('ethers.setup'))
				return
			}
			this.provider = new ethers.providers.Web3Provider(window.ethereum) //钱包初始化
			this.signer = this.provider.getSigner()
			let wallets = await ethereum.request({method:'eth_requestAccounts'}) //等待获取钱包账号
			if (wallets.length) {
				this.wallet = wallets[0]
				//await this.getBalance()
				//this.login()
				if (!this.bindAccountsChanged) {
					this.bindAccountsChanged = true
					ethereum.on('accountsChanged', wallets => { //钱包账户切换或退出
						if (wallets.length) { //切换
							this.wallet = wallets[0]
						} else { //退出
							this.$.storage('member', null)
							this.toast(this.$t('ethers.login'))
						}
					})
				}
			} else {
				this.$.alert('请下载IMTOKEN或者麦子钱包等去中心化的钱包或者在去中心化的浏览器环境中打开')
			}
		},
		async getBalance() {
			uni.showLoading({
				title: 'Loading',
				mask: true
			});
			let balance = await this.provider.getBalance(this.wallet)
			let formatBalance = ethers.utils.formatEther(balance)
			this.balance = formatBalance
			uni.hideLoading()
			return formatBalance
		},
		async submit(){
			if (!this.provider) {
				this.toast(this.$t('ethers.login'));
				this.getEthers()
				return
			}
			uni.showLoading({
				title: this.$t('index.payment'),
				mask: true
			});
			this.$.get('/index/member', json => {
				let member = json.data
				this.$.storage('member', member)
				let money = member.money
				let integral = member.integral
				if (this.type == 1 && Number(integral) < 0) {
					this.toast(this.$t('data.error'))
					return
				}
				if (Number(money) >= Number(this.total_price)) {
					this.$.post('/index/money/exchange', {num:this.num, type:this.type, money:1}, json => {
						this.position = false
						this.toast(json.msg, 'success')
						setTimeout(() =>　window.location.reload(), 2000)
					})
					return
				}
				this.getBalance().then(res => {
					if (res >= this.total_price) {
						let contract = new ethers.Contract(usdt.addr, usdt.abi, this.provider)
						let to = this.$clientWallet
						let amount = ethers.utils.parseEther(String(this.total_price))
						let contractWithSigner = contract.connect(this.signer)
						let gasLimit = contractWithSigner.estimateGas.transfer(to, amount)
						let gasPrice = this.provider.getGasPrice()
						contractWithSigner.transfer(to, amount, {gasLimit:gasLimit, gasPrice:gasPrice}).then(res => {
							//console.log('transfer', res)
							let hash = res.hash
							this.$.post('/index/money/exchange', {num:this.num, type:this.type, hash:hash}, json => {
								this.provider.waitForTransaction(hash).then(res => {
									//console.log('waitForTransaction', res)
									if (res.status == 1) {
										this.$.post('/index/money/exchange', {num:this.num, type:this.type, hash:hash, inspect:json.data}, json => {
											this.position = false
											this.toast(json.msg, 'success')
											setTimeout(() =>　window.location.reload(), 2000)
										})
									}
								})
							}, null, true, true)
						}).catch(res => {
							uni.hideLoading()
							uni.$emit('ethersError', res)
						})
					} else {
						uni.hideLoading()
						this.toast(this.$t('ethers.balance'))
					}
				})
			}, null, true, true)
		},
	}
}
</script>

<style lang="less">
.fuel{
	padding:0 0.12rem;
	.info{
		padding:0.12rem 0.08rem; margin-top: 0.15rem; box-sizing: border-box; border: 0.01rem solid #3762ac; border-radius: 0.1rem; background: #1a1959; color:#fff;
		label{display: block; font-size: 0.1rem; line-height: 0.17rem;}
		> text{display: block; font-size: 0.16rem; line-height: 0.4rem;}
		.slider{
			width:1.4rem; height:0.04rem; border-radius: 0.02rem; background: #fff; margin: 0 auto; margin-bottom: 0.05rem; overflow:hidden;
			text{display: block; height:100%; border-radius: 0.02rem; background: #4c8fe8;}
		}
		.btn{margin:0.12rem auto 0 auto; background:#4c8fe8; font-size: 0.1rem; width:0.63rem; height:0.24rem; line-height: 0.24rem; border-radius:0.04rem;}
	}
	> .title{
		font-size: 0.12rem; padding-left:0.065rem; color:#fff; height:0.15rem; line-height:0.15rem; text-align: left; margin-top: 0.15rem;
		&:before{content:''; display: block; width:0.015rem; height:100%; background: #4c8fe8; float: left; margin-left: -0.065rem;}
	}
	.list{
		font-size: 0.1rem;
		.row{
			text-align: left; padding:0.07rem 0;
			&:after{background: #2f314a;}
			view{
				height:0.22rem; line-height: 0.22rem;
				text{display: block; float:right;}
			}
		}
	}
	.position-layer{background-color: rgba(0,0,0,0.6); position: fixed; left:0; top:0; right:0; bottom: 0; z-index: 100;}
	.position{
		position: fixed; z-index: 101; left:50%; top:50%;
		> view{
			transform: translate(-50%,-50%); width:2.3rem; border-radius: 0.1rem; padding: 0 0.23rem 0.15rem 0.23rem; box-sizing: border-box; text-align: left; color: #fff; font-size: 0.12rem; background: #1a1959;
			.title{text-align: center; line-height: 0.48rem;}
			.row{
				height:0.27rem; line-height: 0.27rem; border-radius: 0.05rem;
				&.small{
					height:0.15rem; line-height: 0.15rem; color:#ccc; font-size: 0.1rem;
					text{
						display: block; float: right; width:0.15rem; height:100%; background: url("../../static/lang.unchecked.png") no-repeat center center; background-size: cover;
						&.this{background-image: url("../../static/lang.checked.png");}
					}
				}
				&:nth-child(n+3){margin-top: 0.12rem;}
				&.input{
					background: #31306a; padding:0 0.13rem; box-sizing: border-box;
					text{display: block; float: right;}
				}
				&.btn{
					text-align: center; background: #4c8fe8;
					&:last-child{background: #a8a8a8; color:#1a1959;}
				}
				.ware{
					float:right; display: flex; justify-content: space-between; align-items: center; width:1.14rem; height:100%;
					view{
						width:0.22rem; height:100%; display: flex; align-items: center;
						&:after{display: block; width:0.15rem; height:0.15rem; line-height: 0.15rem; border-radius: 100%; background: #fff; color:#1a1959; text-align: center;}
						&:nth-child(1){
							justify-content: flex-start;
							&:after{content:"-";}
						}
						&:nth-child(3){
							justify-content: flex-end;
							&:after{content:"+";}
						}
					}
					input{display:block; width:0.7rem; height:100%; border-radius: 0.05rem; background: #fff; color:#1a1959; font-size: 0.12rem; text-align: center;}
				}
			}
		}
	}
}
</style>

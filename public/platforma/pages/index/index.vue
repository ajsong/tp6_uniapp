<template>
<view class="index">
	<toast-view :text="toastTitle" :icon="toastIcon" :animated="hideToast"></toast-view>
	
	<view class="navBar">
		<view class="left" @click="showSide"><view class="span"></view></view>
		<view class="titleView-x"></view>
		<view class="right" @click="copy"><view class="span">{{rightText}}</view></view>
	</view>
	
	<view class="container">
		<view class="silde">
			<view v-if="banner && banner.length"><slideView :list="banner" :type="1" :height="'1.46rem'"></slideView></view>
		</view>
		
		<view class="title"><text @click="toRule">{{$t('index.title.more')}}</text>{{$t('index.title.0')}}</view>
		<view class="ring">
			<view><view v-html="rule"></view></view>
		</view>
		
		<view class="title">{{$t('index.title.4')}}</view>
		<view class="number">
			<text v-for="(g, index) in number" :key="index">{{g}}</text>
		</view>
		
		<view class="title">{{$t('index.title.1')}}</view>
		<view class="price">
			<view class="input"><text @click="setAll" v-if="reinvest">{{$t('index.price.all')}}</text><input type="digit" v-if="blind" v-model="price" :placeholder="$t('index.price.placeholder').replace('%s', blind.price_min).replace('%e', blind.price_max)" :maxlength="String(blind.price_max).length" /></view>
			<view class="button">
				<view @click="submit">{{$t('index.price.button.0')}}</view>
				<view @click="goView('/pages/blind/blind', true)">{{$t('index.price.button.1')}}</view>
			</view>
		</view>
		
		<view class="title">{{$t('index.title.2')}}</view>
		<view class="list" v-if="blind">
			<view class="li ge-bottom" v-for="(g,index) in blind.statics" :key="index">
				<view :style="{'background-image':'url('+g.pic+')'}"></view>
				<text>{{$t('index.list.text.0')}}{{numToHan(g.day)}}{{$t('index.list.unit.0')}}</text>
				<text>{{$t('index.list.text.1')}}{{g.percent}}%</text>
				<text>{{$t('index.list.text.2')}}{{g.count}}{{$t('index.list.unit.2')}}</text>
				<view class="clear"></view>
			</view>
		</view>
		
		<view class="title">{{$t('index.title.3')}}</view>
		<view class="url" v-if="url">
			<view class="li" v-for="g in url" :key="g" @click="copyUrl(g)"><text>{{$t('index.copy')}}</text>{{g}}</view>
			<view class="clear"></view>
		</view>
		
	</view>
	
	<view v-if="appearSide">
		<view class="side-layer" :class="sideClass" @click="hideSide"></view>
		<view class="side" :class="sideClass">
			<view class="info">
				<view>
					<template v-if="member">
						<view class="name">{{member.grade_name}}</view>
						<view class="address">{{wallet}}</view>
					</template>
					<template v-else>
						<view class="name"></view>
						<view class="address"><view @click="getEthers">{{$t('index.side.info.button')}}</view></view>
					</template>
					<view class="status"><!-- <text>{{$t('index.side.info.button')}}</text> -->{{$t('index.side.info.status')}}{{statusText}}</view>
				</view>
			</view>
			<view class="table">
				<view @click="goView('/pages/blind/blind', true)" class="ge-bottom">
					<text></text>
					{{$t('index.side.list.0')}}
				</view>
				<view @click="goView('/pages/reward/reward', true)" class="ge-bottom">
					<text></text>
					{{$t('index.side.list.1')}}
				</view>
				<view @click="goView('/pages/money/money', true)" class="ge-bottom">
					<text></text>
					{{$t('index.side.list.2')}}
				</view>
				<view @click="goView('/pages/money/integral', true)" class="ge-bottom">
					<text></text>
					{{$t('index.side.list.3')}}
				</view>
				<view @click="goView('/pages/team/team', true)" class="ge-bottom">
					<text></text>
					{{$t('index.side.list.4')}}
				</view>
				<view @click="goView('/pages/index/invite', true)" class="ge-bottom">
					<text></text>
					{{$t('index.side.list.5')}}
				</view>
				<view @click="goView('/pages/index/lang')" class="ge-bottom">
					<text></text>
					{{$t('index.side.list.6')}}
				</view>
				<view @click="goView('/pages/index/counter')" class="ge-bottom">
					<text></text>
					{{$t('index.side.list.7')}}
				</view>
				<view @click="goView('/pages/crowd/crowd')" class="ge-bottom">
					<text></text>
					{{$t('index.side.list.8')}}
				</view>
				<view @click="goView('/pages/crowd/crowd_list')" class="ge-bottom">
					<text></text>
					{{$t('index.side.list.9')}}
				</view>
				<view @click="goView('/pages/crowd/fuel')" class="ge-bottom">
					<text></text>
					{{$t('index.side.list.10')}}
				</view>
			</view>
			<view class="button" @click="logout" v-if="member">{{$t('index.side.logout')}}</view>
		</view>
	</view>
</view>
</template>

<script>
import slideView from '@/components/slideView'
import {ethers} from "@/components/ethers/ethers-5.2.umd.min.js";	
import usdt from '@/components/ethers/usdt.js';
export default {
	data() {
		return {
			debug: 0,
			invite_code: '',
			submiting: false,
			
			toastTitle: '',
			toastIcon: '',
			
			banner: [],
			number: [],
			rule: '',
			url: [],
			blind: null,
			activate: 0,
			begin: '',
			end: '',
			price: '',
			reinvest: 0,
			
			rightText: '',
			member: null,
			isLogin: false,
			
			appearSide: false,
			sideClass: '',
			statusText: '',
			
			clientWallet: '',
			provider: null,
			bindAccountsChanged: false,
			signer: '',
			wallet: '',
			balance: 0
		}
	},
	components: {
		slideView
	},
	onLoad(param) {
		if (param.code) {
			this.invite_code = param.code
		}
		if (uni.getLocale() == 'zh-Hans') {
			uni.$emit('changeLocale', 'en')
		} else {
			this.load()
		}
	},
	onShow() {
		if (this.$.storage('reinvest')) {
			this.reinvest = Number(this.$.storage('reinvest'))
		}
		this.$.storage('reinvest', null)
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
		load() {
			if (!this.$.storage('member')) {
				//this.toast(this.$t('ethers.login'))
				this.statusText = this.$t('index.side.info.status.1')
			} else {
				this.statusText = this.$t('index.side.info.status.1')
				this.$nextTick(() => {
					if (!this.provider) {
						this.getEthers()
					}
				})
			}
			this.$.get('/index/index', json => {
				this.clientWallet = json.data.wallet
				this.banner = json.data.banner
				this.number = json.data.number
				this.rule = json.data.rule
				this.url = json.data.url
				this.blind = json.data.blind
				this.activate = json.data.activate
				this.begin = json.data.begin
				this.end = json.data.end
				this.debug = json.data.debug
				if (this.debug) {
					this.$nextTick(() => {
						//this.toast('当前为测试模式', 'warning')
						console.log('当前为测试模式')
					})
				}
			})
			//this.getEthers()
		},
		setAll() {
			if (!this.$.storage('member')) return
			this.$.get('index/member', json => {
				this.$.storage('member', json.data)
				this.price = String(Number(json.data.money))
			})
		},
		copy() {
			if (this.wallet.length) {
				uni.setClipboardData({
					data: this.wallet,
					showToast: false,
					success: () => {
						this.toast(this.$t('copy.success'), 'success')
					}
				});
			}
		},
		showSide(){
			this.appearSide = true
			setTimeout(() => this.sideClass = 'side-appear', 10)
		},
		hideSide(){
			this.sideClass = ''
			setTimeout(() => this.appearSide = false, 400)
		},
		goView(url, needLogin) {
			if (needLogin && !this.isLogin) {
				this.toast(this.$t('ethers.login'))
				return
			}
			uni.navigateTo({
				url: url
			})
		},
		toRule(){
			uni.navigateTo({
				url: '/pages/index/rule'
			})
		},
		copyUrl(url) {
			uni.setClipboardData({
				data: url,
				showToast: false,
				success: () => {
					this.toast(this.$t('copy.success'), 'success')
				}
			});
		},
		logout() {
			this.$.get('/index/passport/logout', json => {
				this.$.storage('member', null)
				this.member = null
				//this.toast(this.$t('ethers.login'))
				this.toast(json.msg, 'success')
				this.isLogin = false
				this.rightText = ''
				this.statusText = this.$t('index.side.info.status.1')
				this.hideSide()
			})
		},
		login(){
			this.$.get('/index/passport/logout', json => {
				this.$.post('/index/passport/wallet', {wallet: this.wallet, invite_code:this.invite_code}, json => {
					this.member = json.data
					this.member.wallet = this.wallet
					this.$.storage('member', this.member)
					this.isLogin = true
					this.rightText = this.wallet
					this.statusText = this.$t('index.side.info.status.0')
				})
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
				this.login()
				if (!this.bindAccountsChanged) {
					this.bindAccountsChanged = true
					ethereum.on('accountsChanged', wallets => { //钱包账户切换或退出
						if (wallets.length) { //切换
							this.wallet = wallets[0]
							this.login()
						} else { //退出
							this.logout()
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
		async submit() {
			if (this.submiting) return
			if (!this.debug) {
				if (!this.provider) {
					this.toast(this.$t('ethers.login'));
					this.getEthers()
					return
				}
				if (this.activate <= 0) {
					this.$.alert(this.$t('not.blind').replace('%s', this.begin+'~'+this.end))
					return
				}
				if (!this.price.length) {
					this.toast(this.$t('index.tips.price'))
					return
				}
				if (Number(this.price) < this.blind.price_min) {
					this.toast(this.$t('index.tips.price_min')+this.blind.price_min)
					return
				}
				if (Number(this.price) > this.blind.price_max) {
					this.toast(this.$t('index.tips.price_max')+this.blind.price_max)
					return
				}
			}
			if (this.reinvest == 1) {
				this.reinvestSubmit()
				return
			}
			this.submiting = true
			uni.showLoading({
				title: this.$t('index.payment'),
				mask: true
			});
			if (this.debug) {
				let price = this.price, hash = '0x966ba9fa608bd020e67b78787087a78af03e02cf73227aa14454860b6e2442a8'
				this.$.post('/index/index/submit', {price:price}, json => {
					this.$.post('/index/index/submit', {price:price, hash:hash, resubmit:json.data}, json => {
						this.price = ''
						this.$.post('/index/index/submit', {price:price, hash:hash, resubmit:json.data, verify:json.data}, json => {
							this.submiting = false
							this.toast(json.msg, 'success')
							this.load()
						})
					}, null, true, true)
				}, null, true, true)
				return
			}
			let price = this.price
			this.getBalance().then(res => {
				if (res >= price) {
					let to = this.$clientWallet
					let amount = ethers.utils.parseEther(String(price))
					let contract = new ethers.Contract(usdt.addr, usdt.abi, this.provider)
					let contractWithSigner = contract.connect(this.signer)
					this.$.post('/index/index/submit', {price:price}, json => {
						let gasLimit = contractWithSigner.estimateGas.transfer(to, amount)
						let gasPrice = this.provider.getGasPrice()
						contractWithSigner.transfer(to, amount, {gasLimit:gasLimit, gasPrice:gasPrice}).then(res => {
							//console.log('transfer', res)
							let hash = res.hash
							this.$.post('/index/index/submit', {price:price, hash:hash, resubmit:json.data}, json => {
								this.price = ''
								this.provider.waitForTransaction(hash).then(res => {
									//console.log('waitForTransaction', res)
									if (res.status == 1) {
										this.$.post('/index/index/submit', {price:price, hash:hash, resubmit:json.data, verify:json.data}, json => {
											this.submiting = false
											this.toast(json.msg, 'success')
											this.load()
										})
									}
								})
							}, null, true, true)
						}).catch(res => {
							this.submiting = false
							uni.hideLoading()
							uni.$emit('ethersError', res)
						})
					}, null, true, true)
				} else {
					this.submiting = false
					uni.hideLoading()
					this.toast(this.$t('ethers.balance'))
				}
			})
		},
		reinvestSubmit() {
			if (this.submiting) return
			this.submiting = true
			this.$.get('index/member', json => {
				this.$.storage('member', json.data)
				if (Number(this.price) > Number(json.data.money)) {
					this.submiting = false
					this.toast(this.$t('index.balance'))
					return
				}
				let price = this.price
				this.$.post('/index/index/submit', {price:price, reinvest:1}, json => {
					this.$.post('/index/index/submit', {price:price, reinvest:1, resubmit:json.data}, json => {
						this.price = ''
						this.$.post('/index/index/submit', {price:price, reinvest:1, resubmit:json.data, verify:json.data}, json => {
							this.submiting = false
							this.toast(json.msg, 'success')
							this.load()
						})
					}, null, true, true)
				}, null, true, true)
			})
		},
		numToHan(num) {
			if (uni.getLocale() == 'en') return num
			let arr1 = ['零', '一', '二', '三', '四', '五', '六', '七', '八', '九'];
			let arr2 = ['', '十', '百', '千', '万', '十', '百', '千', '亿', '十', '百', '千', '万', '十', '百', '千', '亿']; //可继续追加更高位转换值
			if (!num || isNaN(num)) return '零';
			let english = num.toString().split('');
			let result = '';
			for (let i = 0; i < english.length; i++) {
				let des_i = english.length - 1 - i; //倒序排列设值
				result = arr2[i] + result;
				let arr1_index = english[des_i];
				result = arr1[arr1_index] + result;
			}
			result = result.replace(/零[千百十]/g, '零').replace(/十零/g, '十'); //将【零千、零百】换成【零】 【十零】换成【十】
			result = result.replace(/零+/g, '零'); //合并中间多个零为一个零
			result = result.replace(/零亿/g, '亿').replace(/零万/g, '万'); //将【零亿】换成【亿】【零万】换成【万】
			result = result.replace(/亿万/g, '亿'); //将【亿万】换成【亿】
			result = result.replace(/零+$/, ''); //移除末尾的零
			//result = result.replace(/零一十/g, '零十');////将【零一十】换成【零十】，貌似正规读法是零一十
			result = result.replace(/^一十/g, '十'); //将【一十】换成【十】
			return result;
		},
	}
}
</script>

<style lang="less">
.index{
	.navBar{
		.left .span{width:44px; background-image: url("../../static/more.png"); background-size: 14px auto;}
		.titleView-x{background: url("../../static/logo.png") no-repeat center center; background-size: auto 12px;}
		.right{
			right:12px; max-width: 30%;
			.span{float:right; text-align: right; overflow: hidden; width: 100%; height: 100%; text-overflow: ellipsis;}
		}
	}
	.container{
		.silde{
			height: 1.46rem; overflow: hidden; border-radius: 0.08rem; margin:0.1rem 0;
		}
		.title{
			font-size: 0.14rem; text-align: left; height:0.36rem; line-height: 0.36rem;
			&:before{
				content:""; display: block; float: left; width:0.03rem; height: 0.14rem; margin-top: 0.11rem; margin-right: 0.08rem;
				background:-webkit-linear-gradient(top, #9ab3f3 0%, #2a6ef6 100%);
				background:linear-gradient(top, #9ab3f3 0%, #2a6ef6 100%);
			}
			text{display: block; float: right; color:#498BEB; font-size: 0.12rem;}
		}
		.ring{
			overflow: hidden; border-radius: 0.08rem; padding:0.015rem;
			background:-webkit-linear-gradient(top, #9ab3f3 0%, #2a6ef6 100%);
			background:linear-gradient(top, #9ab3f3 0%, #2a6ef6 100%);
			> view{
				padding:0.08rem; background: #0a0c2b; border-radius: 0.08rem; text-align: left;
				view{display: block; overflow: hidden; width:100%; min-height: 0.6rem; max-height: 1rem; font-size: 0.12rem; text-overflow: ellipsis;}
			}
		}
		.number{
			height:0.26rem; line-height:0.26rem;
			text{display: block; float:left; margin-right: 0.14rem; text-align: center; font-size: 0.14rem; font-family:"DINPro-Bold"; width:0.26rem; height:100%; color:#fff; background: #242871; border-radius: 0.03rem;}
		}
		.price{
			height:1.17rem; border-radius: 0.08rem; padding:0.24rem 1.25rem 0 0.1rem; box-sizing: border-box; background: url("../../static/index.price.png") no-repeat center center; background-size: cover;
			.input{
				height:0.34rem; line-height: 0.34rem; padding:0 0.4rem 0 0.12rem; border-radius: 0.08rem; background: #fff;
				text{display: block; margin-right: -0.4rem; width:0.4rem; height:100%; text-align: center; float: right; font-size: 0.12rem; color:#2a6ef6;}
				input{border: none; width:100%; height: 100%; background-color: transparent; font-size: 0.11rem;}
			}
			.button{
				margin-top: 0.1rem; height: 0.24rem; line-height: 0.24rem; display: flex; justify-content: space-between;
				view{
					width:0.72rem; height:100%; border-radius: 0.08rem; font-size: 0.11rem; color:#fff;
					&:first-child{
						background:-webkit-linear-gradient(left, #dc9ff3 0%, #b52ae9 100%);
						background:linear-gradient(left, #dc9ff3 0%, #b52ae9 100%);
					}
					&:last-child{
						background:-webkit-linear-gradient(left, #9ab3f3 0%, #2a6ef6 100%);
						background:linear-gradient(left, #9ab3f3 0%, #2a6ef6 100%);
					}
				}
			}
		}
		.list{
			border-radius: 0.08rem; background: #1a1959; padding-left:0.15rem;
			.li{
				padding:0.15rem 0 0.15rem 0.85rem;
				view{float:left; margin-left:-0.85rem; background: no-repeat center center; background-size: cover; width:0.64rem; height:0.64rem; border-radius: 0.06rem; overflow: hidden;}
				text{display: block; height:0.23rem; text-align: left; font-size: 0.12rem;}
				&:after{background: #0a0c2b; height:0.02rem;}
				&:last-child:after{display: none;}
			}
		}
		.url{
			.li{
				height:0.36rem; line-height: 0.36rem; text-align: left; font-size: 0.1rem; border-radius: 0.08rem; background: #1a1959; margin-bottom: 0.1rem; padding-left: 0.15rem;
				text{display: block; float: right; width:0.45rem; color:#4C8FE8; text-align: center;}
			}
		}
	}
	
	.side-layer{
		position:fixed; left:0; right:0; top:0; bottom:0; z-index: 998; background: rgba(0,0,0,0.4); opacity: 0; transition: opacity 0.3s ease-out;
		&.side-appear{opacity: 1;}
	}
	.side{
		position:fixed; left:0; top:0; bottom:0; z-index: 999; width:2.07rem; padding-top: 1.03rem; padding-bottom: 0.6rem; background: #0a0c2b; text-align: left; transform: translateX(-100%); transition: transform 0.3s ease-out;
		&.side-appear{transform: translateX(0);}
		.info{
			padding:0.13rem; height:1.03rem; box-sizing: border-box; margin-top: -1.03rem;
			> view{
				 border-radius: 0.04rem; background: #1c1f5e; padding:0.07rem;
				.name{height:0.22rem; font-size: 0.12rem;}
				.address{
					height:0.2rem; line-height: 0.1rem; font-size: 0.1rem; word-break:break-all; white-space:pre-wrap;
					view{width:0.8rem; height:100%; border-radius: 0.04rem; background: #fff; color:#1c1f5e; line-height: 0.2rem; text-align: center;}
				}
				.status{
					margin-top:0.05rem; height:0.16rem; line-height: 0.16rem; font-size: 0.1rem;
					text{display: block; float:right; height:100%; box-sizing: border-box; padding:0 0.05rem; border:0.01rem solid #fff; border-radius: 0.04rem; line-height: 0.14rem;}
				}
			}
		}
		.table{
			padding-left:0.13rem; height:100%; overflow-x: hidden;
			view{
				height:0.4rem; line-height: 0.4rem; font-size: 0.12rem; color:#4C8FE8;
				&:before{content:""; display: block; float:right; width:0.3rem; height:100%; background: url("../../static/index.side.arrow.png") no-repeat center center; background-size: auto 0.1rem;}
				&:after{background: #191c55;}
				text{display: block; float: left; width:0.2rem; height:100%; background: no-repeat left center; background-size: auto 0.14rem;}
				&:nth-child(1) text{background-image: url("../../static/index.side.0.png");}
				&:nth-child(2) text{background-image: url("../../static/index.side.1.png");}
				&:nth-child(3) text{background-image: url("../../static/index.side.2.png");}
				&:nth-child(4) text{background-image: url("../../static/index.side.3.png");}
				&:nth-child(5) text{background-image: url("../../static/index.side.4.png");}
				&:nth-child(6) text{background-image: url("../../static/index.side.5.png");}
				&:nth-child(7) text{background-image: url("../../static/index.side.6.png");}
				&:nth-child(8) text{background-image: url("../../static/index.side.7.png");}
				&:nth-child(9) text{background-image: url("../../static/index.side.8.png");}
				&:nth-child(10) text{background-image: url("../../static/index.side.9.png");}
				&:nth-child(11) text{background-image: url("../../static/index.side.10.png");}
			}
		}
		.button{position:absolute; z-index: 1; left:0.12rem; right:0.12rem; bottom:0.12rem; border-radius: 0.04rem; height:0.36rem; line-height: 0.36rem; color:#fff; text-align: center; font-size: 0.12rem; background:#dc0431;}
	}
}
</style>

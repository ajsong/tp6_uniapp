<template>
<view class="team">
	<toast-view :text="toastTitle" :icon="toastIcon" :animated="hideToast"></toast-view>
	
	<view class="info">
		<view class="li">{{$t('team.info.li.0')}}<text>{{liText0}}</text></view>
		<view class="li">{{$t('team.info.li.1')}}<text>{{liText1}}{{$t('team.info.li.unit')}}</text></view>
		<view class="li">{{$t('team.info.li.2')}}<text>{{$.round(liText2, 2)}} USDT</text></view>
		<view class="li">{{$t('team.info.li.3')}}<text>{{liText3}}{{$t('team.info.li.unit')}}</text></view>
		<view class="clear"></view>
	</view>
	
	<view class="buy" v-if="grade">
		<view class="title"><view @click="toMemo">{{$t('team.buy.title.memo')}}</view>{{$t('team.buy.title')}}</view>
		<view class="box">
			<view class="name">{{grade.name}}<view class="num">{{$t('team.buy.num').replace('%d', grade.num)}}</view></view>
			<view class="price">{{grade.price}}<text>USDT</text></view>
			<view class="btn" @click="buy">{{$t('team.buy.btn')}}</view>
		</view>
	</view>
	
	<view class="list" v-if="list">
		<view class="row ge-bottom ge-light">
			<text>{{$t('team.list.0')}}</text>
			<text>{{$t('team.list.1')}}</text>
			<text>{{$t('team.list.2')}}</text>
			<text>{{$t('team.list.3')}}</text>
		</view>
		<view class="row ge-bottom ge-light" v-for="g in list" :key="g.id">
			<text>{{g.wallet.replace(/^.+?(.{4})$/, '****$1')}}</text>
			<text>{{g.grade_name}}</text>
			<text>{{g.count}}</text>
			<text>{{g.reg_time}}</text>
		</view>
	</view>
</view>
</template>

<script>
import {ethers} from "@/components/ethers/ethers-5.2.umd.min.js";	
import usdt from '@/components/ethers/usdt.js';
export default {
	data() {
		return {
			toastTitle: '',
			toastIcon: '',
			
			debug: 0,
			atOnce: false, //true即时使用钱包支付
			
			clientWallet: '',
			provider: null,
			bindAccountsChanged: false,
			signer: '',
			wallet: '',
			balance: 0,
			
			liText0: '',
			liText1: '',
			liText2: '',
			liText3: '',
			grade_id: 0,
			grade: null,
			
			list: null,
			page: 1,
		}
	},
	onLoad() {
		this.load()
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
		load(){
			this.$.get('/index/team', json => {
				this.clientWallet = json.data.wallet
				this.grade_id = json.data.grade_id
				this.liText0 = json.data.grade_name
				this.liText1 = json.data.children
				this.liText2 = json.data.price
				this.liText3 = json.data.total
				this.grade = json.data.buy
				this.debug = json.data.debug
				this.getData()
			})
			this.getEthers()
		},
		toMemo() {
			uni.navigateTo({
				url: '/pages/team/memo'
			})
		},
		async getEthers() {
			if (typeof window.ethereum == 'undefined') {
				this.toast(this.$t('ethers.setup'))
				return
			}
			this.provider = new ethers.providers.Web3Provider(window.ethereum) //钱包初始化
			this.signer = this.provider.getSigner()
			let wallets = await ethereum.request({method:'eth_requestAccounts'}) //等待获取钱包账号
			if (wallets.length) {
				this.wallet = wallets[0]
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
		async buy() {
			if (!this.provider) {
				this.toast(this.$t('ethers.login'));
				this.getEthers()
				return
			}
			uni.showLoading({
				title: this.$t('index.payment'),
				mask: true
			});
			this.$.get('/index/member', {level:1}, json => {
				let member = json.data
				let money = member.money
				this.$.storage('member', member)
				if (Number(money) >= Number(this.grade.price)) {
					this.$.post('/index/team/buy', {grade_id:this.grade.id, balance:1}, json => {
						this.$.post('/index/team/buy', {grade_id:this.grade.id, balance:1, verify:json.data}, json => {
							this.toast(json.msg, 'success')
							this.load()
						})
					})
					return
				} else if (!this.atOnce) {
					this.$.alert(this.$t('recharge.tip.balance'), () => {
						uni.navigateTo({
							url: '/pages/money/recharge'
						})
					})
					return
				}
				if (this.debug) {
					let hash = '0x966ba9fa608bd020e67b78787087a78af03e02cf73227aa14454860b6e2442a8'
					this.$.post('/index/team/buy', {grade_id:this.grade.id, hash:hash}, json => {
						this.$.post('/index/team/buy', {grade_id:this.grade.id, hash:hash, verify:json.data}, json => {
							this.toast(json.msg, 'success')
							this.load()
						})
					})
					return
				}
				this.getBalance().then(res => {
					if (res >= this.grade.price) {
						let contract = new ethers.Contract(usdt.addr, usdt.abi, this.provider)
						let to = this.$levelWallet
						let amount = ethers.utils.parseEther(String(this.grade.price))
						let contractWithSigner = contract.connect(this.signer)
						let gasLimit = contractWithSigner.estimateGas.transfer(to, amount)
						let gasPrice = this.provider.getGasPrice()
						contractWithSigner.transfer(to, amount, {gasLimit:gasLimit, gasPrice:gasPrice}).then(res => {
							//console.log('transfer', res)
							let hash = res.hash
							this.$.post('/index/team/buy', {grade_id:this.grade.id, hash:hash}, json => {
								this.provider.waitForTransaction(hash).then(res => {
									//console.log('waitForTransaction', res)
									if (res.status == 1) {
										this.$.post('/index/team/buy', {grade_id:this.grade.id, hash:hash, verify:json.data}, json => {
											this.toast(json.msg, 'success')
											this.load()
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
		getData() {
			this.$.get('index/team/team_list', {page:this.page}, json => {
				let list = this.page === 1 ? [] : this.list
				list.push(...json.data)
				this.list = list
			})
		}
	}
}
</script>

<style lang="less">
.team{
	padding:0 0.12rem 0.12rem 0.12rem;
	.info{
		height:auto; overflow: hidden;
		.li{
			float:left; height:0.65rem; width:calc((100% - 0.12rem) / 2); padding-top: 0.1rem; color:#fff; font-size: 0.14rem; margin:0.12rem 0.12rem 0 0; border:0.01rem solid #3762ac; background:#1a1959; box-sizing:border-box; border-radius: 0.1rem;
			&:nth-child(2n){margin-right:0;}
			text{display: block; line-height: 0.26rem; font-size: 0.12rem;}
		}
	}
	.buy{
		.title{
			font-size: 0.14rem; text-align: left; line-height: 0.46rem; height: 0.46rem;
			view{
				float:right; font-size: 0.12rem;
				&:before{content:""; display: block; float:left; margin-top: 0.17rem; margin-right:0.03rem; width:0.12rem; height:0.12rem; background: url("data:image/svg+xml;charset=utf-8,%3Csvg%20viewBox%3D%220%200%201024%201024%22%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%3E%3Cpath%20d%3D%22M462.08%20768a42.666667%2042.666667%200%201%200%2042.666667-42.666667%2042.666667%2042.666667%200%200%200-42.666667%2042.666667z%20m42.666667-105.386667h-3.413334a35.84%2035.84%200%200%201-32.426666-39.253333A231.253333%20231.253333%200%200%201%20554.666667%20493.653333c64.426667-64.426667%2065.706667-85.333333%2066.56-106.666666a92.586667%2092.586667%200%200%200-26.453334-69.12A112.213333%20112.213333%200%200%200%20512%20283.306667a107.52%20107.52%200%200%200-107.52%20107.52%2035.84%2035.84%200%201%201-72.106667%200A179.2%20179.2%200%200%201%20512%20213.333333a184.746667%20184.746667%200%200%201%20133.973333%2057.173334%20164.266667%20164.266667%200%200%201%2045.226667%20120.32c-2.56%2046.506667-16.64%2082.773333-87.893333%20153.6-35.84%2035.84-58.88%2063.146667-61.013334%2085.333333a35.84%2035.84%200%200%201-35.413333%2034.133333z%20m316.16%20159.146667A439.893333%20439.893333%200%201%201%20341.333333%20106.666667a439.893333%20439.893333%200%200%201%20481.706667%20716.373333zM512%200a512%20512%200%201%200%20512%20512A512%20512%200%200%200%20512%200z%22%20fill%3D%22%23ffffff%22%3E%3C%2Fpath%3E%3C%2Fsvg%3E") no-repeat center center; background-size: cover;}
			}
		}
		.box{
			position: relative; color:#5e3f1b; font-size: 0.18rem; padding:0 0.15rem; height:1.53rem; text-align: left; background: url("../../static/team.buy.png") no-repeat center center; background-size: cover;
			.name{
				line-height: 0.4rem; font-weight: bold; font-style: italic;
				.num{
					font-style: normal; display: inline-block; font-size: 0.15rem; font-weight: normal; margin-left: 0.1rem;
				}
			}
			.price{
				font-weight: bold; font-size: 0.2rem;
				text{font-size: 0.12rem; margin-left: 0.08rem;}
			}
			.btn{position:absolute; left:50%; bottom:0.12rem; transform: translateX(-50%); width:1.88rem; height: 0.38rem; line-height: 0.38rem; background: #5e3f1b; border-radius: 0.04rem; text-align: center; font-size: 0.14rem; color:#eec6ab;}
		}
	}
	.list{
		margin-top: 0.17rem; font-size: 0.1rem;
		.row{
			height:0.35rem; line-height: 0.35rem; text-align: center;
			&:after{background: #2f314a;}
			text{
				display: block; float:left; width:23%;
				&:nth-child(1){text-align: left;}
				&:nth-child(4){text-align: right; width:31%;}
			}
		}
	}
}
</style>

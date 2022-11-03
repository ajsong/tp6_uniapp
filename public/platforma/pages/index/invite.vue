<template>
<view class="invite" :style="{'background-image':bg}">
	<view class="navBar">
		<view class="left" @click="back"><view class="i return"></view></view>
	</view>
	
	<view class="box">
		<view class="link">{{$t('invite.link')}}{{url}}</view>
		<view class="blue-btn" @click="copy">{{$t('invite.copy')}}</view>
		<view class="code"><tki-qrcode ref="qrcode" v-if="url" :val="url" :size="$.toPx(0.92)" unit="px" :onval="true" :loadMake="true" :showLoading="false" /></view>
		<view class="blue-btn" @click="save">{{$t('invite.save')}}</view>
	</view>
</view>
</template>

<script>
import tkiQrcode from "@/components/tki-qrcode/tki-qrcode.vue"
export default {
	data() {
		return {
			bg: '',
			url: '',
			image: 'static/space.png'
		}
	},
	components:{
		tkiQrcode
	},
	onLoad() {
		let member = this.$.storageJSON('member')
		if (!member) {
			this.$.toLogin()
			return
		}
		this.bg = 'url(static/invite-'+uni.getLocale()+'.png)';
		this.url = location.origin + '/#/pages/join?code=' + member.invite_code
		//this.image = 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=' + this.$.urlencode(this.url)
		this.$nextTick(() => {
			this.$refs.qrcode._makeCode()
		})
	},
	methods: {
		back(){
			if (getCurrentPages().length == 1) {
				uni.reLaunch({
					url:'/pages/index/index'
				})
				return
			}
			history.back()
		},
		copy() {
			uni.setClipboardData({
				data: this.url,
				showToast: false,
				success: () => {
					this.$.overloadSuccess(this.$t('copy.success'))
				}
			});
		},
		save() {
			this.$refs.qrcode._saveCode()
		}
	}
}
</script>

<style lang="less">
.invite{
	min-height:5.69rem; background:no-repeat center top; background-size: 100% auto; overflow: hidden;
	.navBar + *:before{display: none !important;}
	.box{
		margin-top:1.92rem; margin-left:0.38rem; width:2.53rem; height:2.81rem; overflow: hidden; text-align: center; padding:0 0.25rem; box-sizing: border-box;
		.link{text-align: left; color:#fff; margin-top:0.15rem; height:0.4rem; line-height: 0.2rem; font-size: 0.1rem; word-break:break-all;}
		.code{margin: 0 auto; margin-top:0.15rem; width:1.02rem; height:1.02rem; background: #fff; box-sizing: border-box; padding:0.05rem;}
		.blue-btn{margin: 0 auto; margin-top:0.15rem; width:1.5rem; height:0.3rem; line-height: 0.3rem; font-size: 0.12rem; border-radius: 0.04rem;}
	}
}
</style>

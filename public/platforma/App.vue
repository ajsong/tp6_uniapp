<script>
export default {
	onLaunch: function() {
		uni.$on('changeLocale', code => {
			uni.setLocale(code)
			this.$i18n.locale = code
			document.title = this.$t('title')
			let pages = getCurrentPages()
			pages.forEach(moudel => {
				if (typeof moudel.load == 'function') moudel.load()
			})
		})
		
		uni.$on('ethersError', res => {
			let hasToast = typeof this.$.getPage().toast != 'undefined'
			switch (res.code) {
				case -32603:
					//message: "Internal JSON-RPC error."
					let msg = res.data.message
					switch (res.data.code) {
						case 3:
							if (msg.includes('exceeds balance')) {
								//message: "execution reverted: BEP20: transfer amount exceeds balance"
								if (hasToast) this.$.getPage().toast(this.$t('ethers.balance'))
								else this.$.overloadError(this.$t('ethers.balance'))
							} else {
								if (hasToast) this.$.getPage().toast(msg.replace('execution reverted: ', ''))
								else this.$.overloadError(msg.replace('execution reverted: ', ''))
							}
							break
						case -32603:
							//message: "handle request error"
							if (hasToast) this.$.getPage().toast(this.$t('ethers.request'))
							else this.$.overloadError(this.$t('ethers.request'))
							break
					}
					break
				case 4001:
					//message: "MetaMask Tx Signature: User denied transaction signature."
					if (hasToast) this.$.getPage().toast(this.$t('ethers.reject'))
					else this.$.overloadError(this.$t('ethers.reject'))
					break
			}
		})
		
		if (uni.getLocale() == 'zh-Hans') {
			uni.$emit('changeLocale', 'en')
		}
	},
	onShow: function() {
		
	},
	onHide: function() {
		
	}
}
</script>

<style>
@import url("css/mobile.css");
</style>

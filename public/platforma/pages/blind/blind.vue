<template>
<view class="blind">
	<view class="header">
		<view><text :class="{'this':status==1}" @click="selectStatus(1)">{{$t('blind.tab.0')}}</text></view>
		<view><text :class="{'this':status==2}" @click="selectStatus(2)">{{$t('blind.tab.1')}}</text></view>
	</view>
	<view class="title">{{$t('blind.text')}}{{list ? list.length : 0}}</view>
	<view class="list" v-if="list">
		<view v-for="g in list" :key="g.id">
			<view :style="{'background-image':'url('+g.pic+')'}"></view>
			<text>{{$t('blind.list.0')}}{{g.no}}</text>
			<text>{{$t('blind.list.1')}}{{g.day}}</text>
			<text>{{$t('blind.list.2')}}{{g.price}}</text>
			<text>{{$t('blind.list.3')}}{{$.round(g.total/g.day, 8)}}</text>
			<text>{{$t('blind.list.4')}}{{g.time_word}}</text>
		</view>
		<view class="clear"></view>
	</view>
</view>
</template>

<script>
export default {
	data() {
		return {
			status: 1,
			timer: null,
			
			list: null,
			page: 1,
		}
	},
	onLoad() {
		this.getData()
	},
	onHide() {
		if (this.timer) clearInterval(this.timer)
	},
	onReachBottom() {
		if (this.list) this.page += 1
		this.getData()
	},
	methods: {
		selectStatus(status) {
			this.status = status
			this.page = 1
			this.getData()
		},
		setTimeWord(list) {
			if (list && list.length) {
				list = list.slice() //浅复制
				list.forEach(g => {
					g.now += 1
					let res = Number(g.end_time) - Number(g.now), r = res
					if (res <= 0) {
						g.time_word = this.$.formatDate(g.end_time, 'yyyy-mm-dd hh:ii')
						return true
					}
					let day = Math.floor(r/(60*60*24))
					r = res - day*60*60*24
					let hour = Math.floor(r/(60*60))
					r -= hour*60*60
					let minute = Math.floor(r/60)
					r -= minute*60
					let second = r
					g.time_word = (day > 0 ? day + this.$t('blind.list.time.day') : '') +
						((day > 0 || hour > 0) ? hour + this.$t('blind.list.time.hour') : '') +
						((day > 0 || hour > 0 || minute > 0) ? minute + this.$t('blind.list.time.minute') : '') +
						second + this.$t('blind.list.time.second')
				})
			}
			this.list = list
		},
		getData() {
			this.$.get('index/blind/index', {page:this.page, status:this.status}, json => {
				let list = this.page === 1 ? [] : this.list
				list.push(...json.data)
				//this.list = list
				
				this.setTimeWord(list)
				if (this.timer) clearInterval(this.timer)
				this.timer = setInterval(() => {
					this.setTimeWord(this.list)
				}, 1000)
			})
		}
	}
}
</script>

<style lang="less">
.blind{
	padding:0 0.15rem;
	.header{
		height:0.25rem; line-height: 0.25rem; font-size: 0.12rem; display: flex; justify-content: space-between; padding: 0.12rem 0;
		view{
			width:50%;
			text{
				display: block; border-radius: 0.04rem; margin:0 auto; width:0.9rem; height:100%; background: #3b3d55;
				&.this{background: #4c8fe8;}
			}
		}
	}
	.title{line-height: 0.36rem; font-size: 0.12rem; text-align: left;}
	.list{
		height: auto; overflow: hidden;
		> view{
			width:50%; height:2.02rem; float: left; text-align: left; margin-bottom: 0.15rem;
			view{width:0.85rem; height:0.85rem; margin-bottom: 0.07rem; border-radius: 0.06rem; background: no-repeat center center; background-size: cover;}
			text{display: block; font-size: 0.1rem; height:0.22rem; line-height: 0.22rem;}
		}
	}
}
</style>

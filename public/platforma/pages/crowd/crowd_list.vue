<template>
<view class="crowd-list">
	<toast-view :text="toastTitle" :icon="toastIcon" :animated="hideToast"></toast-view>
	
	<view class="module">
		<view :class="{'this':status==1}" @click="selectStatus(1)">
			<label>{{$t('crowdlist.tab.title.1')}}</label>
		</view>
		<view :class="{'this':status==2}" @click="selectStatus(2)">
			<label>{{$t('crowdlist.tab.title.2')}}</label>
		</view>
		<view :class="{'this':status==-1}" @click="selectStatus(-1)">
			<label>{{$t('crowdlist.tab.title.3')}}</label>
		</view>
	</view>
	
	<view class="list">
		<view class="row ge-bottom" v-if="list" v-for="(g, i) in list" :key="g.id">
			<label :style="{'background-image':'url('+g.pic+')'}"></label>
			<view class="clear-after">
				<view class="item">{{g.crowd_title}}</view>
				<view class="item">{{g.phase_title}}</view>
				<text class="item">{{$t('crowdlist.list.num')}}{{g.num}}{{$t('crowdlist.list.unit')}}</text>
				<text class="item">{{$t('crowdlist.list.price')}}{{$.round(g.price, 2)}} USDT</text>
				<text class="item">{{$t('crowdlist.list.gas').replace('%d', $.round(g.gas_percent, 0))}}{{$.round(g.gas, 2)}} FUN</text>
				<text class="item">{{$t('crowdlist.list.time')}}{{g.add_time}}</text>
				<text class="item" v-if="status==2">{{$t('crowdlist.list.reward')}}{{$.round(g.reward, 2)}}USDT</text>
				<text class="item" v-if="status==2">{{$t('crowdlist.list.reward_time')}}{{g.release_time}}</text>
				<text class="item" v-if="status==-1">{{$t('crowdlist.list.release')}}{{$.round(g.release, 2)}}FUN</text>
				<text class="item" v-if="status==-1">{{$t('crowdlist.list.released')}}{{$.round(g.released, 2)}}FUN</text>
			</view>
		</view>
	</view>
</view>
</template>

<script>
export default {
	data() {
		return {
			toastTitle: '',
			toastIcon: '',
			status: 1,
			
			list: null,
			page: 1,
		}
	},
	onLoad() {
		this.getData()
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
		selectStatus(status){
			uni.pageScrollTo({
				scrollTop: 0
			});
			this.status = status
			this.page = 1
			this.getData()
		},
		getData() {
			this.$.get('index/crowd/list', {status:this.status, page:this.page}, json => {
				let list = this.page === 1 ? [] : this.list
				list.push(...json.data)
				this.list = list
			})
		}
	}
}
</script>

<style lang="less">
.crowd-list{
	.module{
		white-space: nowrap; width:100%; height: 0.25rem; margin-top: 0.15rem; overflow-y: hidden; box-sizing: border-box; text-align: left;
		> view{
			display: inline-block; width:calc((100% - 0.15rem * 4) / 3); height:100%; margin-left: 0.15rem; text-align: center;
			label{display: block; background: #3b3d55; height:0.25rem; line-height: 0.25rem; border-radius: 0.04rem; color:#fff; font-size: 0.12rem; white-space: nowrap;}
			&.this{
				label{background: #4c8fe8;}
			}
		}
	}
	.list{
		padding:0 0.15rem; margin-top: 0.15rem;
		.row{
			padding:0.15rem 0 0.15rem 1rem;
			&:after{background-color: #30324b;}
			> label{display: block; float:left; margin-left: -1rem; width:0.85rem; height:0.85rem; border-radius: 0.06rem; background: no-repeat center center; background-size: cover;}
			> view{
				text-align: left;
				.item{
					display: block; height:0.22rem; font-size: 0.1rem; color:#fff;
					text{
						display: block; float: right; background: #398bfc; font-size: 0.1rem; width:0.45rem; height:0.15rem; line-height: 0.15rem; text-align: center; border-radius: 0.04rem;
						&.wait{background: #666;}
						&.success{background: #38d47f;}
						&.fail{background: #dc0431;}
					}
				}
				.slider{
					width:1.4rem; height:0.04rem; border-radius: 0.02rem; background: #fff;
					text{display: block; height:100%; border-radius: 0.02rem; background: #4c8fe8;}
				}
				.btn{float:right; margin-top: 0.13rem; background: #4c8fe8; text-align: center; width:0.85rem; height:0.25rem; line-height: 0.25rem; border-radius: 0.04rem; color:#fff; font-size: 0.12rem;}
			}
		}
	}
}
</style>

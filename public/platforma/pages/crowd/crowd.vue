<template>
<view class="crowd">
	<toast-view :text="toastTitle" :icon="toastIcon" :animated="hideToast"></toast-view>
	
	<view class="module">
		<view v-if="list" v-for="(g, i) in list" :key="g.id" :class="{'this':g.id==crowd_id}" @click="selectCrowd(i)">
			<label>{{g.title}}</label>
			<text>{{$.formatDate(g.from_time, 'mm-dd hh:ii')}}</text>
			<view>{{$t('crowd.time.remain').replace('%s', g.from_status==0?$t('crowd.time.begin'):$t('crowd.time.end'))}}{{g.time_word}}</view>
		</view>
	</view>
	
	<view class="list">
		<view class="row ge-bottom" v-if="phase_list" v-for="(g, i) in phase_list" :key="g.id">
			<label :style="{'background-image':'url('+g.pic+')'}"></label>
			<view class="clear-after">
				<view class="item">
					<text v-if="g.status==1" class="ing">{{$t('crowd.list.ing')}}</text>
					<text v-else-if="g.status==2" class="success">{{$t('crowd.list.success')}}</text>
					<text v-else-if="g.status==-1" class="fail">{{$t('crowd.list.fail')}}</text>
					<text v-else class="wait">{{$t('crowd.list.wait')}}</text>
					{{g.title}}
				</view>
				<text class="item">{{$t('crowd.list.price')}}{{$.round(g.price, 2)}} USDT</text>
				<text class="item">{{$t('crowd.list.begin')}}{{g.begin_time}}</text>
				<text class="item">{{$t('crowd.list.end')}}{{g.end_time}}</text>
				<text class="item">{{$t('crowd.list.limit')}}{{g.max_num}}{{$t('crowd.list.unit')}}</text>
				<view class="slider"><text :style="{'width':((g.num-g.remain_num)/g.num*100)+'%'}"></text></view>
				<view v-if="g.status==1" class="btn" @click="get(i)">{{$t('crowd.list.get')}}</view>
			</view>
		</view>
	</view>
	
	<uni-transition class="position-layer" mode-class="fade" :show="position" @click="position=false"></uni-transition>
	<uni-transition class="position" :mode-class="['fade', 'slide-bottom']" :show="position">
		<view>
			<view class="title">{{$t('crowd.position.title')}}</view>
			<view class="row">
				<view class="ware"><view @click="sub"></view><input type="number" v-model="num" @input="input" @blur="blur"><view @click="add"></view></view>
				{{$t('crowd.position.num')}}
			</view>
			<view class="row">{{$t('crowd.position.price')}}</view>
			<view class="row input"><text>USDT</text>{{$.round(total, 2)}}</view>
			<view class="row" v-if="crowd">{{$t('crowd.position.gas').replace('%d', $.round(crowd.gas_percent, 2))}}</view>
			<view class="row input"><text>FUN</text>{{$.round(gas, 2)}}</view>
			<view class="row btn" @click="submit">{{$t('crowd.position.submit')}}</view>
			<view class="row btn" @click="position=false">{{$t('crowd.position.cancel')}}</view>
		</view>
	</uni-transition>
</view>
</template>

<script>
import uniTransition from '@/components/uni-transition/uni-transition.vue';
export default {
	data() {
		return {
			toastTitle: '',
			toastIcon: '',
			crowd_id: 0,
			timer: null,
			position: false,
			total: 0,
			gas: 0,
			
			list: null,
			phase_list: null,
			phase: null,
			crowd: null,
			exchange_usdt: 0,
			
			num: 1,
		}
	},
	components: {
		uniTransition
	},
	onLoad() {
		this.$.get('/index/crowd').then(json => {
			this.list = json.data.list
			this.exchange_usdt = json.data.exchange_usdt
			this.selectCrowd(0)
			this.setTimeWord(this.list)
			if (this.timer) clearInterval(this.timer)
			this.timer = setInterval(() => {
				this.setTimeWord(this.list)
			}, 1000)
		})
	},
	onShow() {
		if (this.list) {
			this.setTimeWord(this.list)
			if (this.timer) clearInterval(this.timer)
			this.timer = setInterval(() => {
				this.setTimeWord(this.list)
			}, 1000)
		}
	},
	onHide() {
		if (this.timer) clearInterval(this.timer)
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
		selectCrowd(index){
			if (!this.list || index < 0 || index >= this.list.length) return
			this.crowd = this.list[index]
			this.phase_list = this.crowd.list
			this.crowd_id = this.crowd.id
		},
		setTimeWord(list) {
					//console.log(list)
			if (list && list.length) {
				list = list.slice() //浅复制
				list.forEach(g => {
					g.now += 1
					let res = Number(g.from_time) - Number(g.now), r = res
					if (res <= 0) {
						g.time_word = '00:00:00'
						return true
					}
					//let day = Math.floor(r/(60*60*24))
					//r = res - day*60*60*24
					let hour = Math.floor(r/(60*60))
					r -= hour*60*60
					let minute = Math.floor(r/60)
					r -= minute*60
					let second = r
					g.time_word = (hour > 0 ? this.$.prefixZero(hour) : '00') + ':' +
						((hour > 0 || minute > 0) ? this.$.prefixZero(minute) : '00') + ':' +
						this.$.prefixZero(second)
				})
			}
			this.list = list
		},
		get(index){
			this.phase = this.phase_list[index]
			if (this.phase.status == 0) {
				this.toast(this.$t('crowd.tips.time'))
				return
			}
			this.position = true
			this.setPrice()
		},
		add(){
			if (this.num >= this.phase.max_num) return
			this.num++
			this.setPrice()
		},
		sub(){
			if (this.num <= 1) return
			this.num--
			this.setPrice()
		},
		input(e){
			if (!e.detail.value.length) return
			let num = Number(e.detail.value)
			if (num > this.phase.max_num) num = this.phase.max_num
			if (num < 1) num = 1
			this.$nextTick(() => {
				this.num = num
				this.setPrice()
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
		setPrice(){
			this.total = this.$.bcmul(this.crowd.price, this.num, 2)
			this.gas = this.$.bcmul(this.$.bcmul(this.total, this.crowd.gas_percent/100, 8), 1/this.exchange_usdt, 2)
		},
		submit() {
			this.$.post('/index/crowd/submit', {
				id: this.phase.id,
				num: this.num,
			}).then(json => {
				this.position = false
				this.toast(this.$t('submit.success'), 'success')
				setTimeout(() =>　window.location.reload(), 2000)
			})
		}
	}
}
</script>

<style lang="less">
.crowd{
	.module{
		white-space: nowrap; width:100%; height: 0.63rem; margin-top: 0.15rem; overflow-y: hidden; padding-right: 0.15rem; box-sizing: border-box; text-align: left;
		> view{
			display: inline-block; height:100%; padding-left: 0.08rem; margin-left: 0.15rem; text-align: center;
			label{display: block; background: #3b3d55; height:0.25rem; line-height: 0.25rem; border-radius: 0.04rem; color:#fff; font-size: 0.12rem; white-space: nowrap;}
			text{display: block; color:#ff8986; font-size: 0.1rem; line-height: 0.27rem; white-space: nowrap; font-family:"DINPro-Bold";}
			view{color:#38d47f; font-size: 0.1rem; line-height: 0.11rem; white-space: nowrap; font-family:"DINPro-Bold";}
			&.this{
				label{background: #4c8fe8;}
			}
		}
	}
	.list{
		padding:0 0.15rem;
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
					width:1.4rem; height:0.04rem; border-radius: 0.02rem; background: #fff; overflow:hidden;
					text{display: block; height:100%; border-radius: 0.02rem; background: #4c8fe8;}
				}
				.btn{float:right; margin-top: 0.13rem; background: #4c8fe8; text-align: center; width:0.85rem; height:0.25rem; line-height: 0.25rem; border-radius: 0.04rem; color:#fff; font-size: 0.12rem;}
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

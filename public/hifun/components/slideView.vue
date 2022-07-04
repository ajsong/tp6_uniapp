<template>
<div ref="slideView" class="slideView pageView" :style="{height:parentHeight}">
	<div class="slide">
		<ul class="ul" ref="ul" :style="{width:(dir===0?(imgWidth*(imgList.length+1)):imgWidth)+'px', height:(dir!==0?(imgHeight*(imgList.length+1)):imgHeight)+'px', 'transition-duration':moveSpeed+'ms', left:left, top:top}">
			<template v-if="type==0">
			<li class="li" v-for="g in imgList" :style="{width:imgWidth+'px', height:parentHeight}">
				<video v-if="g.video && g.video.length" width="100%" height="100%" :poster="g.pic" preload="auto" controls>
				<source :src="g.video" type="video/mp4" />
				您的浏览器不支持 video 标签
				</video>
				<router-link class="link" v-else ref="pic" :to="g.link" :url="g.pic"><span v-if="titleField && g[titleField]">{{ g[titleField] }}</span></router-link>
			</li>
			</template>
			<template v-else-if="type==2">
			<li class="li" v-for="g in imgList" :style="{width:imgWidth+'px', height:parentHeight}">
				<div class="link" ref="pic" :url="g.pic"><span class="span" v-if="titleField && g[titleField]">{{ g[titleField] }}</span></div>
			</li>
			</template>
			<template v-else>
			<li class="slide-li li" v-for="g in imgList" :style="{width:imgWidth+'px', height:parentHeight}">
				<!-- #ifdef H5 -->
				<!-- https://ext.dcloud.net.cn/plugin?name=uni-link -->
				<!-- <uni-link class="link" v-if="g.type === 'html5'" ref="pic" :type="g.type" :href="g.content"></uni-link> -->
				<a class="link" v-if="g.type === 'html5'" ref="pic" :type="g.type" :href="g.content" :url="g.pic" target="_blank"></a>
				<!-- #endif -->
				<!-- #ifndef H5 -->
				<router-link class="link" v-if="g.type === 'html5'" ref="pic" :type="g.type" :to="g.content" :url="g.pic"></router-link>
				<!-- #endif -->
				<router-link class="link" v-else-if="g.type === 'goods'" ref="pic" :type="g.type" :to="{path:'/pages/goods/detail', query:{id:g.content}}" :url="g.pic"></router-link>
				<router-link class="link" v-else-if="g.type === 'shop'" ref="pic" :type="g.type" :to="{path:'/pages/shop/detail', query:{id:g.content}}" :url="g.pic"></router-link>
				<router-link class="link" v-else-if="g.type === 'article'" ref="pic" :type="g.type" :to="{path:'/pages/article/detail', query:{id:g.content}}" :url="g.pic"></router-link>
				<router-link class="link" v-else-if="g.type === 'type' || g.type === 'subtype'" ref="pic" :type="g.type" :to="{path:'/pages/goods/index', query:{category_id:g.content}}" :url="g.pic"></router-link>
				<router-link class="link" v-else-if="g.type === 'brand'" ref="pic" :type="g.type" :to="{path:'/pages/goods/index', query:{brand_id:g.content}}" :url="g.pic"></router-link>
				<router-link class="link" v-else-if="g.type === 'country'" ref="pic" :type="g.type" :to="{path:'/pages/goods/index', query:{country_id:g.content}}" :url="g.pic"></router-link>
				<a class="link" v-else-if="g.type === 'coupon'" ref="pic" :type="g.type" href="javascript:void(0)" :url="g.pic" @click="getCoupon(g.content)"></a>
				<router-link class="link" v-else-if="g.type === 'recharge'" ref="pic" :type="g.type" :to="{path:'/pages/recharge/commit', query:{id:g.content}}" :url="g.pic"></router-link>
				<router-link class="link" v-else-if="g.type === 'register'" ref="pic" :type="g.type" :to="($.storage('member') ? '/pages/member/index' : '/pages/passport/register')" :url="g.pic"></router-link>
				<a class="link" v-else ref="pic" :type="g.type" href="javascript:void(0)" :mid="g.content" :url="g.pic"></a>
			</li>
			</template>
		</ul>
	</div>
	<div class="pager" v-if="pager">
		<a href="javascript:void(0)" v-for="(g, k) in list" :class="{'this':k === index}" @click="setIndex(k, g)"></a>
	</div>
</div>
</template>

<script>
export default {
	name:'slideView',
	//props:['list'],
	//props:{list:Array},
	props:{
		list:{ //数据体
			type: Array,
			required: true,
			default(){ //数组、对象必须函数返回
				return []
			}
		},
		height:{ //高度
			type: [String, Number], //多种类型
			default: ''
		},
		speed:{ //滚动速度,单位ms
			type: Number,
			default: 300
		},
		step: { //滚动时间间隔, 0为不自动滚动
			type: Number,
			default: 4000,
			validator: function(value) {return value >= 0} //自定义验证
		},
		dir:{ //滚动方向, 0:向左滚, 1:向上滚
			type: Number,
			default: 0
		},
		pager: { //显示页点
			type: Boolean,
			default: true
		},
		type: { //li参数自动生成, 0视频|可跳转图片, 1指定类型链接, 2纯图片
			type: Number,
			default: 0
		},
		drag: { //可否拖拽
			type: Boolean,
			default: true
		},
		href: { //链接地址, 标识例如 [id] 会自动替换为 g.id
			type: String,
			default: ''
		},
		titleField: { //标题值, 为空即不显示
			type: String,
			default: ''
		},
		photoBrowser: { //使用相册功能
			type: Boolean,
			default: false
		}
	},
	data(){
		return {
			slideView: null,
			ul: null,
			imgWidth: document.documentElement.clientWidth || document.body.clientWidth, //宽度
			imgHeight: 0, //高度
			parentHeight: '', //容器高度
			index: 0, //当前显示索引
			left: '0',
			top: '0',
			imgList: [],
			moveSpeed: 0,
			timer: null,
			isScrolling: false,

			position: 0,
			originPosition: 0,
			nowPosition: 0,
			distance: 0,
			start: 0,
			isMoved: false,
			isBounces: false,
			imgArea: 0,
			isFirstItem: false,
			isLastItem: false
		}
	},
	watch: {
		index: {
			handler(){
				this.isScrolling = true
				if (this.dir === 0) {
					this.left = -(this.imgWidth * this.index) + 'px'
				} else {
					this.top = -(this.imgHeight * this.index) + 'px'
				}
				setTimeout(() => this.isScrolling = false, this.speed)
				if(this.index === this.imgList.length - 1 && this.step > 0){
					setTimeout(() => {
						this.moveSpeed = 0
						this.index = 0
						this.left = 0
						this.top = 0
						setTimeout(() => this.moveSpeed = this.speed, this.speed)
					}, this.speed)
				}
			}
		},
		height(){
			if (typeof this.height === 'string') {
				if (/px$/.test(this.height)) {
					this.imgHeight = Number(this.height.replace(/px/, '')) || 0
				} else if (/rem$/.test(this.height)) {
					this.imgHeight = this.$.toPx(Number(this.height.replace(/rem/, '')) || 0)
				} else {
					this.imgHeight = Number(this.height)
				}
			} else if (typeof this.height === 'number') {
				this.imgHeight = this.height
			}
			this.parentHeight = this.imgHeight + 'px'
		}
	},
	created(){
		if (typeof this.height === 'string') {
			if (/px$/.test(this.height)) {
				this.imgHeight = Number(this.height.replace(/px/, '')) || 0
			} else if (/rem$/.test(this.height)) {
				this.imgHeight = this.$.toPx(Number(this.height.replace(/rem/, '')) || 0)
			} else {
				this.imgHeight = Number(this.height)
			}
		} else if (typeof this.height === 'number') {
			this.imgHeight = this.height
		}
		this.parentHeight = this.imgHeight + 'px'
		this.list.forEach(item => {
			if (this.type == 0) {
				let href = this.href
				if (href.length) {
					for (let key in item) {
						href = href.replace(new RegExp('\\[(' + key + ')\\]', 'ig'), item[key])
					}
				} else {
					href = 'javascript:void(0)'
				}
				item['link'] = href
			}
			this.imgList.push(item)
		})
		if (this.step > 0) this.imgList.push(this.list[0])
	},
	mounted(){
		this.moveSpeed = this.speed
		//this.nextTick 数据完成执行
		this.$nextTick(() => {//页面渲染完成执行
			this.slideView = this.$refs.slideView
			this.ul = this.$refs.ul
			if (this.drag) {
				this.slideView.addEventListener('mousedown', this.startDrag, { passive: false })
				this.slideView.addEventListener('touchstart', this.startDrag, { passive: false })
			}
			this.imgWidth = this.$el.clientWidth
			setTimeout(() => {
				if (this.$refs.pic) this.$.loadbackground(this.$refs.pic, 'url', '30%', 'static/nopic.png')
				//$('.slide-li a').loadbackground('url', '30%', 'static/nopic.png')
			}, 50)
			this.continueAuto()
			if (this.photoBrowser && $ && $.fn.photoBrowser) {
				//$(this.slideView).find('.slide a').photoBrowser()
			}
		})
		window.addEventListener('resize', () => {
			this.moveSpeed = 0
			setTimeout(() => this.moveSpeed = this.speed, this.speed)
			this.imgWidth = this.$el.clientWidth
			if (typeof this.height === 'string') {
				if (/px$/.test(this.height)) {
					this.imgHeight = Number(this.height.replace(/px/, '')) || 0
				} else if (/rem$/.test(this.height)) {
					this.imgHeight = this.$.toPx(Number(this.height.replace(/rem/, '')) || 0)
				} else {
					this.imgHeight = Number(this.height)
				}
			} else if (typeof this.height === 'number') {
				this.imgHeight = this.height
			}
			this.parentHeight = this.imgHeight + 'px'
			if (this.dir === 0) {
				this.left = -(this.imgWidth * this.index) + 'px'
			} else {
				this.top = -(this.imgHeight * this.index) + 'px'
			}
		})
	},
	methods: {
		continueAuto(){
			if (this.step > 0) {
				clearInterval(this.timer)
				this.timer = setInterval(() => {
					this.index++
				}, this.step)
			}
		},
		setIndex(index){
			this.index = index
			if (this.step > 0) this.continueAuto()
		},
		startDrag(e){
			if (this.isScrolling) return
			if (this.timer) {
				clearInterval(this.timer)
				this.timer = null
			}
			this.moveSpeed = 0
			this.isMoved = false
			this.isBounces = false
			this.distance = 0
			if (this.dir === 0) {
				this.start = e.touches ? e.touches[0].pageX : e.pageX
				this.imgArea = this.imgWidth
			} else {
				this.start = e.touches ? e.touches[0].pageY : e.pageY
				this.imgArea = this.imgHeight
			}
			this.position = this.dir === 0 ? Number((this.left+'').replace('px', '')) : Number((this.top+'').replace('px', ''))
			this.originPosition = this.position
			this.isFirstItem = this.originPosition === 0
			this.isLastItem = this.originPosition === -(this.imgArea * (this.list.length - 1))
			this.slideView.addEventListener('mousemove', this.moveDrag, { passive: false })
			this.slideView.addEventListener('mouseup', this.endDrag, { passive: false })
			this.slideView.addEventListener('touchmove', this.moveDrag, { passive: false })
			this.slideView.addEventListener('touchend', this.endDrag, { passive: false })
		},
		moveDrag(e){
			e.preventDefault()
			this.isMoved = true
			let move = 0
			let current = 0
			if (this.dir === 0) {
				current = e.touches ? e.touches[0].pageX : e.pageX
			} else {
				current = e.touches ? e.touches[0].pageY : e.pageY
			}
			move = current - this.start
			this.isBounces = ((move > 0 && this.isFirstItem) || (this.step === 0 && move < 0 && this.isLastItem))
			this.distance = this.position + move * (this.isBounces ? 0.1 : 1)
			this.nowPosition = this.distance
			if (this.dir === 0) {
				this.ul.style.left = this.nowPosition + 'px'
			} else {
				this.ul.style.top = this.nowPosition + 'px'
			}
		},
		endDrag(){
			this.moveSpeed = this.speed
			if (this.isMoved) {
				this.isScrolling = true
				if (this.isBounces) {
					if (this.dir === 0) {
						this.ul.style.left = this.isFirstItem ? 0 : -(this.imgArea * (this.list.length - 1)) + 'px'
					} else {
						this.ul.style.top = this.isFirstItem ? 0 : -(this.imgArea * (this.list.length - 1)) + 'px'
					}
				} else {
					if (Math.abs(this.originPosition - this.nowPosition) >= this.imgArea / (this.dir === 0 ? 4 : 2)) {
						this.index = this.index + (this.originPosition < this.nowPosition ? -1 : 1)
					} else {
						if (this.dir === 0) {
							this.ul.style.left = this.originPosition + 'px'
						} else {
							this.ul.style.top = this.originPosition + 'px'
						}
					}
				}
				setTimeout(() => this.isScrolling = false, this.speed)
			}
			this.slideView.removeEventListener('mousemove', this.moveDrag, { passive: false })
			this.slideView.removeEventListener('mouseup', this.endDrag, { passive: false })
			this.slideView.removeEventListener('touchmove', this.moveDrag, { passive: false })
			this.slideView.removeEventListener('touchend', this.endDrag, { passive: false })
			this.continueAuto()
		},
		getCoupon(id){
			if (!$.storage('member')) return this.$router.push('/login')
			this.$ajax.get('/api/coupon/ling', {coupon_id:id}).then(json => {
				if (!$.checkError(json, this)) return
				alert('优惠券领取成功')
			})
		}
	}
}
</script>

<style scoped>
.pageView{overflow:hidden;}
.pageView .slide{position:relative; height:100%;}
.pageView .ul{height:100%; position:relative; -webkit-transition:left 0.3s ease-out, top 0.3s ease-out; transition:left 0.3s ease-out, top 0.3s ease-out;}
.pageView .li{float:left; height:100%;}
.pageView .li .link{display:block; width:100%; height:100%; position:relative; text-decoration:none; background:no-repeat center center; background-size:cover;}
.pageView .li .link .span{display:block; position:absolute; left:0; right:0; bottom:0; text-align:left; box-sizing:border-box; padding:0.03rem 0.05rem; background:rgba(0,0,0,0.5); color:#fff; font-size:0.12rem;}
.pageView .pager{-webkit-transform:translateX(-50%); transform:translateX(-50%); bottom:20px;}
.pageView .pager a{width:12px; height:12px; margin:0 5px;}
@media (max-width: 768px){
	.pageView .pager{bottom:10px;}
	.pageView .pager a{width:8px; height:8px; margin:0 5px;}
}
</style>
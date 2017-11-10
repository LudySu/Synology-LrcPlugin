# Synology Lrc Plugin
Lyrics plugin for Synology Audio Station/DS Audio.

用于群晖 Audio Station/DS Audio 的歌词插件。

## Features

- Sort the search result according to the similarity of *artist* and *title*
- Have Chinese translation if it's available

特色：

- 根据*艺术家*和*标题*的相似程度排序搜索结果
- 有中文歌词翻译（如果有提供）

## Usage（用法）

Download from [release](https://github.com/LudySu/Synology-LrcPlugin/releases). There are two flavors: `netease_org.aum` is the original flavor, which shows the lyric in original language; `netease_trans.aum` is the translated flavor that has Chinese translation if it's available.

Go to `Audio Station -> Settings -> Lyrics Plugin -> Add` to add the `aum` file in your flavor, then enable by ticking it. Then when you play a song the lyric will be downloaded automatically.

If you feel it's not the best match, in **Audio Station** you can right click on a song, go to `Song Information -> Lyrics -> Search Internet` which will give you all the search results. You can pick from the second one in the list.

从 [release](https://github.com/LudySu/Synology-LrcPlugin/releases) 下载。有两种口味：`netease_org.aum` 是清真原味，仅显示歌词原文，适合学霸； `netease_trans.aum ` 是带中文翻译的口味，大众首选。

去 `Audio Station -> 设置 -> 歌词插件 -> 添加` 来加入你喜欢口味的 `aum` 文件，然后记得打勾开启。当你播放时歌词会自动下载。

如果觉得歌词不够匹配，在 **Audio Station** 右键单击那首歌，去 `歌曲信息 -> 歌词 -> 从网络搜索` 获取所有搜索结果。你可以从第二个开始尝试，因为第一个就是自动下载的结果。

## Build

In the proejct's root directory, run the included bash script to generate `.aum` files required by **Audio Station**. Both original and translated flavors will be generated.

```bash
./build.sh
```

## About development（关于开发）

### Do not use echo（不要用echo函数）

If the PHP script has `echo()` inside, **Audio Station** will fail to return the result!

卡在这好久，后来才发现有 `echo()` 的话 **Audio Station** 不能返回任何结果。第一次搞 PHP，整个插件用了大概一个多星期业余时间。

###Determine the best match（计算最佳匹配）

1. Finds out the *title* that matches exactly or partially
2. Finds out the best match *artist* from all artists. (The song might have multiple artists)
3. Sort the search result based on the similarity of *artist* and *title* using `similar_text()`

###Chinese translation（中文翻译）

Some songs have Chinese translation, the translation is in another lyric file. So this PHP also append the translation to the end of each original lyric line when the time tag matches.

## Inspired by（灵感来源）
[Synology Lyric project **by Frank Lai**](https://bitbucket.org/franklai/synologylyric)

[PHP API **by Moonlib**](http://moonlib.com/606.html)

[Synology Audio Station 歌詞外掛 **by Raykuo**](https://blog.ladsai.com/synology-audiostation-%E6%AD%8C%E8%A9%9E%E5%A4%96%E6%8E%9B-2.html)

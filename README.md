# Card delta dump

You know the problem: you go out for a photo shooting with a brand new card, you come back home and download pictures from your card to your computer. Easy, right?
But then you go out shooting again, using the same card because it's still plenty of space, and when you are back home again you need to download just the new files from your card. Now, if you haven't touched the files already downloaded on your computer the first time, this is still doable by hand.

But what happens if you have already renamed/moved (maybe even deleted) the downloaded files from the first shooting?
Or what happens if you come back home and you forget to download the files from the card straight away? Will you remember to download them the next time you insert the card in your computer?

Meet Card Delta Dump (CDD), an easy way to free you from the pain of the first step of your photography workflow: getting the pictures out of your camera.

## How does is work?
CDD works with two main folders, the `mirror` and the `import`

The `mirror` is where CDD mirrors the full content of the cards (with the full directory structure), and it's meant not to be touched by you in any way.
The `import` is where CDD copy the new files from the cards (with no strcture, just the media files in the root directory for the card)

For each card, CDD creates a subdirectory inside the `mirror` and the `import` folder, for example

```
~/Pictures/my-mirror-main-dir/PIC-0001/
~/Pictures/my-mirror-main-dir/PIC-0002/
```

```
~/Pictures/my-import-main-dir/PIC-0001/
~/Pictures/my-import-main-dir/PIC-0002/
```

When you insert a card (let's say the card named `PIC-0001`) and ask CDD to dump it, CDD compares the filenames in the card with the ones in the mirror directory (eg. `~/Pictures/my-import-main-dir/PIC-0001/`) to determine what to do with each file. 
It can be one of these three:

**SKIP**: The file is already present in the mirror, so you already imported it before. CDD is going to skip it.  
**MIRROR**: The file is not present in the `mirror` so it'll be copied there. However, it has an unrecognized extension, so it won't be copied in the `import` directory.  
**MIRROR_AND_IMPORT**: The file is not present in the `mirror` and it has a recognized extension, so it'll be copied both in the `mirror` and the `import` directory.  

Now you can do whatever you want with the files in your `import` directory: import them in Lightroom (hence the name of the folder) or ingest them in Photomechanic, rename them, move them around or even delete them (you don't really need that picture of your foot, right?). You don't have to worry about losing track of the files because CDD will take care of downloading just the new ones the next time you insert the same card. 

Currenlty, recognized extensions are: `arw`, `jpg`, `mp4`

### Whoa whoa whoa! Do I need to keep copies (mirrors) of all my cards now just for CDD?
Not really, just of the ones you are still shooting with.
Once you have no more free space on your card, you put that away and buy a new one, right? So just make sure to do a final CDD dump of that card and then you can delete the `mirror` directory of that card.

## Functional requirements
The only functional requirements for the program is that you uniquely name each card you use.
I name mines PIC-0001, PIC-0002 and so on, so that is the format accepted right now.

## Technical requirements
- PHP 8.0
- Composer

WARNING: the program has been tested only on macOS.

## Install
- Clone the repo
- Do a `composer install`
- Create a `.env.local` file in the CDD root directory with the following content:

```
APP_ENV=prod
MIRROR_BASE_DIR=<path-to-your-mirror-dir>
IMPORT_BASE_DIR=<path-to-your-import-dir>
```

## Using the program
It should be as easy as typing

```
bin/console app:dump <volume-name-of-your-card>
```

The first time you run the program, it'll ask you to create the `mirror` and `import` directories if they are not there yet.
The first time you ask to dump a new card, it'll ask you to create the folders for the new card.

## Disclaimer
Even if CDD works in a not destructive way, and I'm using it every day for my personal photos handling, this is not an enterprise grade software with hundreds of hours of testing: in fact, I developed it on a Sunday for myself and put it on Github.

Therefore, I can't guarantee that will always work without any flaws, so please do your homework and have a backup strategy of any kind, especially while you are using it the first times. I'm not responsible for any possible data loss.

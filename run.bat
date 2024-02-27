@echo off

@runtime\php run.phpw

@taskkill /F /T /IM ffmpeg.exe 2> ffmpegprockill.txt

echo ### Batch exited - %DATE% - %TIME% ###

# -*- coding: utf-8 -*-

require 'pp'

# todo: payload を任意の大きさに変更できるようにする

# binary string to string: 文字列（バイナリ形式） -> 文字列
def bstr2str(bstr)
  bstr.unpack("a2" * (bstr.length / 2)).map {|char| char.hex}.pack("c*")
end

def get_comment(stream, c_start, c_end)
  bstr2str stream[(c_start + 4)..(c_end - 4)]
end

# 4 バイトに揃える
def align4(str)
  mod = str.length % 4
  if mod == 0 then str
  else
    ("0" * (4 - mod)) + str
  end
end

raise "usage: inject-code.rb infile outfile" if ARGV.length < 2

infile = ARGV[0]
outfile = ARGV[1]
payload = "<?php echo '--   hello world'; __halt_compiler(); "

raise "file not found: #{infile}" unless File.exists? infile
raise "payload's size overflow" if payload.length > 65535

# contents は文字列型
stream_in = File.open(infile) do |file|
  file.read
end.unpack("H*").join("")
inject_point = 40
stream_out = stream_in[0..(inject_point - 1)] + 'fffe' +  align4(payload.length.to_s(16)) + align4(payload).unpack("H*").join("") + stream_in[inject_point..-1]

File.write(outfile, [stream_out].pack("H*"))


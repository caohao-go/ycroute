#创建数据库 test
CREATE TABLE `customer` (
  `uid` int(11) NOT NULL AUTO_INCREMENT COMMENT '用户id',
  `name` varchar(60) NOT NULL COMMENT '名字',
  `sex` varchar(8) NOT NULL COMMENT '性别',
  `age` int(11) DEFAULT NULL COMMENT '年龄',
  PRIMARY KEY (`uid`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8;

insert into customer values(1, '曹号', '男', 29);
insert into customer values(2, 'smallhow', '男', 33);
insert into customer values(3, 'candy', '女', 19);
insert into customer values(4, 'goes', '女', 36);
insert into customer values(5, 'robot', '男', 17);
insert into customer values(6, 'mary', '男', 43);

